<?php

/*
 * This tool will connect to your Kayako database and export your data into the data/ directory in
 * the standard DeskPRO Import Format.
 *
 * After this tool completes, you will run the standard DeskPRO import process to save the data to
 * your live helpdesk. Refer to the README for more details.
 *
 * 1) Edit the values in the CONFIG section below.
 *
 * 2) Copy this script in to your DeskPRO bin directory. For example:
 *
 *     $ cp kayako /path/to/deskpro/bin/importers
 *
 * 2) Run the import process to fetch all of your data from Kayako:
 *
 *     $ cd /path/to/deskpro
 *     $ bin/import kayako
 *
 * 3) You can now optionally verify the integrity of your data:
 *
 *     $ bin/import verify
 *
 * 4) When you're ready, go ahead and apply the import to your live database:
 *
 *     $ bin/import apply
 *
 * 4) And finally, you can clean up the temporary data files from the filesystem:
 *
 *     $ bin/import clean
 *
 */

########################################################################################################################
# CONFIG
########################################################################################################################

require __DIR__.'/config.php';


########################################################################################################################
# Do not edit below this line
########################################################################################################################

//--------------------
// Setup
//--------------------

/** @var \Application\ImportBundle\ScriptHelper\OutputHelper $output */
/** @var \Application\ImportBundle\ScriptHelper\WriteHelper $writer */
/** @var \Application\ImportBundle\ScriptHelper\FormatHelper $formatter */
/** @var \Application\ImportBundle\ScriptHelper\DbHelper $db */

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
            'person'     => $writer->prepareUserOid($n['userid']),
            'agent'      => $writer->prepareAgentOid($n['staffid']),
            'department' => $n['departmenttitle'],
            'status'     => $statusMapping[$n['ticketstatustitle']],
        ];

        // get ticket messages
        $messagePager = $db->getPager('SELECT * FROM swticketposts WHERE ticketid = :ticket_id', [
            'ticket_id' => $n['ticketid'],
        ]);

        while ($messageData = $messagePager->next()) {
            foreach ($messageData as $m) {
                $ticket['messages'][] = [
                    'oid'     => $m['ticketpostid'],
                    'person'  => $writer->prepareUserOid($m['userid']),
                    'message' => $m['contents'],
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
            'person'  => $writer->prepareAgentOid($n['creatorid']),
            'content' => '',
            'status'  => $statusMapping[$n['articlestatus']],
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
            'person'   => $writer->prepareAgentOid($n['staffid']),
            'content'  => '',
            'status'   => $statusMapping[$n['newsstatus']],
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
