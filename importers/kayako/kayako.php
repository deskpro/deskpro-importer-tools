<?php

########################################################################################################################
# CONFIG
########################################################################################################################

require __DIR__.'/config.php';

########################################################################################################################
# Do not edit below this line
########################################################################################################################

use DeskPRO\ImporterTools\Helpers\OutputHelper;
use DeskPRO\ImporterTools\Helpers\WriteHelper;
use DeskPRO\ImporterTools\Helpers\FormatHelper;
use DeskPRO\ImporterTools\Helpers\DbHelper;

//--------------------
// Setup
//--------------------

$output    = OutputHelper::getHelper();
$writer    = WriteHelper::getHelper();
$formatter = FormatHelper::getHelper();

$db = DbHelper::getHelper();
$db->setCredentials($CONFIG['dbinfo']);

//--------------------
// Organizations
//--------------------

$output->startSection('Organizations');
$pager = $db->getPager('SELECT * FROM swuserorganizations');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        $organization = [
            'name' => $n['organizationname'],
        ];

        // set organization contact data
        // website
        if ($formatter->getFormattedUrl($n['website'])) {
            $organization['contact_data']['website'][] = [
                'url' => $formatter->getFormattedUrl($n['website']),
            ];
        }

        // phone numbers
        if ($formatter->getFormattedNumber($n['phone'])) {
            $organization['contact_data']['phone'][] = [
                'number' => $formatter->getFormattedNumber($n['phone']),
                'type'   => 'phone',
            ];
        }
        if ($formatter->getFormattedNumber($n['fax'])) {
            $organization['contact_data']['phone'][] = [
                'number' => $formatter->getFormattedNumber($n['fax']),
                'type'   => 'fax',
            ];
        }

        // address
        if ($n['address']) {
            $organization['contact_data']['address'][] = [
                'address' => $n['address'],
                'city'    => $n['city'],
                'zip'     => $n['postalcode'],
                'state'   => $n['state'],
                'country' => $n['country'],
            ];
        }

        $writer->writeOrganization($n['userorganizationid'], $organization);
    }
}

//--------------------
// Staff
//--------------------

$output->startSection('Staff');

$staffGroups        = [];
$staffGroupsMapping = [
    'Administrator' => 'All Permissions',
    'Staff'         => 'All Non-Destructive Permissions',
];


$pager = $db->getPager('SELECT * FROM swstaffgroup');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        if (isset($staffGroupsMapping[$n['title']])) {
            $staffGroups[$n['staffgroupid']] = $staffGroupsMapping[$n['title']];
        } else {
            $staffGroups[$n['staffgroupid']] = $n['title'];
        }
    }
}

$pager = $db->getPager('SELECT * FROM swstaff');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        $person = [
            'name'        => $n['fullname'],
            'emails'      => [$n['email']],
            'is_disabled' => !$n['isenabled'],
        ];

        if ($n['staffgroupid']) {
            $person['agent_groups'][] = $staffGroups[$n['staffgroupid']];
        }

        $writer->writeAgent($n['staffid'], $person);
    }
}

//--------------------
// Users
//--------------------

$output->startSection('Users');

$userGroups        = [];
$userGroupsMapping = [
    'Guest' => 'Everyone',
];

$pager = $db->getPager('SELECT * FROM swusergroups');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        if (isset($userGroupsMapping[$n['title']])) {
            $userGroups[$n['usergroupid']] = $userGroupsMapping[$n['title']];
        } else {
            $userGroups[$n['usergroupid']] = $n['title'];
        }
    }
}

$pager = $db->getPager('SELECT * FROM swusers');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        $person = [
            'name'         => $n['fullname'],
            'emails'       => ['imported.user.' . $n['userid'] . '@example.com'],
            'is_disabled'  => !$n['isenabled'],
            'organization' => $n['userorganizationid'],
        ];

        if ($n['usergroupid']) {
            $person['user_groups'][] = $userGroups[$n['usergroupid']];
        }

        if ($person['organization']) {
            $person['organization_position'] = $n['userdesignation'];
        }

        if ($formatter->getFormattedNumber($n['phone'])) {
            $person['contact_data']['phone'][] = [
                'number' => $formatter->getFormattedNumber($n['phone']),
                'type'   => 'phone',
            ];
        }

        $writer->writeUser($n['userid'], $person);
    }
}

//--------------------
// Tickets and messages
//--------------------

$output->startSection('Tickets');
$statusMapping = [
    'Open'        => 'awaiting_agent',
    'In Progress' => 'awaiting_agent',
    'Closed'      => 'resolved',
];

$pager = $db->getPager('SELECT * FROM swtickets');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        $ticket = [
            'subject'    => $n['subject'],
            'person'     => $writer->userOid($n['userid']),
            'agent'      => $writer->agentOid($n['staffid']),
            'department' => $n['departmenttitle'],
            'status'     => isset($statusMapping[$n['ticketstatustitle']]) ? $statusMapping[$n['ticketstatustitle']] : 'awaiting_agent',
        ];

        // get ticket messages
        $messagePager = $db->getPager('SELECT * FROM swticketposts WHERE ticketid = :ticket_id', [
            'ticket_id' => $n['ticketid'],
        ]);

        while ($messageData = $messagePager->next()) {
            foreach ($messageData as $m) {
                $ticket['messages'][] = [
                    'oid'     => 'post_'.$m['ticketpostid'],
                    'person'  => $writer->userOid($m['userid']),
                    'message' => $m['contents'],
                ];
            }
        }

        // get ticket notes
        $notesPager = $db->getPager('SELECT * FROM swticketnotes WHERE linktype = 1 AND linktypeid = :ticket_id', [
            'ticket_id' => $n['ticketid'],
        ]);

        while ($messageData = $notesPager->next()) {
            foreach ($messageData as $m) {
                $ticket['messages'][] = [
                    'oid'     => 'note_'.$m['ticketnoteid'],
                    'person'  => $writer->agentOid($m['staffid']),
                    'message' => $m['note'],
                    'is_note' => true,
                ];
            }
        }

        $writer->writeTicket($n['ticketid'], $ticket);
    }
}

//--------------------
// Article categories
//--------------------

$output->startSection('Article categories');
$getArticleCategories = function ($parentId) use ($db, &$getArticleCategories) {
    $pager = $db->getPager('SELECT * FROM swkbcategories WHERE parentkbcategoryid = :parent_id', [
        'parent_id' => $parentId,
    ]);

    $categories = [];
    while ($data = $pager->next()) {
        foreach ($data as $n) {
            $categories[] = [
                'oid'        => $n['kbcategoryid'],
                'title'      => $n['title'],
                'categories' => $getArticleCategories($n['kbcategoryid']),
            ];
        }
    }

    return $categories;
};

foreach ($getArticleCategories(0) as $category) {
    $writer->writeArticleCategory($category['oid'], $category);
}

//--------------------
// Articles
//--------------------

$output->startSection('Articles');
// todo need status mapping
$statusMapping = [
    1 => 'published',
];

$pager = $db->getPager('SELECT * FROM swkbarticles');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        $article = [
            'title'   => $n['subject'],
            'person'  => $writer->agentOid($n['creatorid']),
            'content' => '',
            'status'  => isset($statusMapping[$n['articlestatus']]) ? $statusMapping[$n['articlestatus']] : 'published',
        ];

        // get article content
        $contentPager = $db->getPager('SELECT * FROM swkbarticledata WHERE kbarticleid = :article_id', [
            'article_id' => $n['kbarticleid'],
        ]);

        while ($data = $contentPager->next()) {
            foreach ($data as $c) {
                $article['content'] .= $c['contents'];
            }
        }

        $writer->writeArticle($n['kbarticleid'], $article);
    }
}

//--------------------
// News
//--------------------

$output->startSection('News');

$newsCategories = [];
$pager = $db->getPager('SELECT * FROM swnewscategories');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        $newsCategories[$n['newscategoryid']] = $n['categorytitle'];
    }
}

// todo need status mapping
$statusMapping = [
    2 => 'published',
];

$pager = $db->getPager('SELECT * FROM swnewsitems');
while ($data = $pager->next()) {
    foreach ($data as $n) {
        $news = [
            'title'    => $n['subject'],
            'person'   => $writer->agentOid($n['staffid']),
            'content'  => '',
            'status'   => isset($statusMapping[$n['newsstatus']]) ? $statusMapping[$n['newsstatus']] : 'published',
        ];

        // get news content
        $contentPager = $db->getPager('SELECT * FROM swnewsitemdata WHERE newsitemid = :news_id', [
            'news_id' => $n['newsitemid'],
        ]);

        while ($data = $contentPager->next()) {
            foreach ($data as $c) {
                $news['content'] .= $c['contents'];
            }
        }

        // get news category
        $category = $db->findOne('SELECT * FROM swnewscategorylinks WHERE newsitemid = :news_id', [
            'news_id' => $n['newsitemid'],
        ]);

        if ($category && isset($newsCategories[$category['newscategoryid']])) {
            $news['category'] = $newsCategories[$category['newscategoryid']];
        }

        $writer->writeNews($n['newsitemid'], $news);
    }
}

//--------------------
// Settings
//--------------------

$output->startSection('Settings');
$settingMapping = [
    'general_producturl'  => 'core.site_url',
    'general_companyname' => 'core.site_name',
];

$pager = $db->getPager('SELECT * FROM swsettings WHERE section = :section AND vkey IN (:setting_names)', [
    'section'       => 'settings',
    'setting_names' => array_keys($settingMapping),
]);

while ($data = $pager->next()) {
    foreach ($data as $n) {
        $writer->writeSetting($n['settingid'], [
            'name'  => $settingMapping[$n['vkey']],
            'value' => $n['data'],
        ]);
    }
}
