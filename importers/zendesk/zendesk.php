<?php

########################################################################################################################
# CONFIG
########################################################################################################################

require __DIR__.'/config.php';

########################################################################################################################
# Do not edit below this line
########################################################################################################################

use DeskPRO\ImporterTools\Importers\ZenDesk\ZenDeskReader;
use DeskPRO\ImporterTools\Importers\ZenDesk\ZenDeskMapper;
use DeskPRO\ImporterTools\Helpers\OutputHelper;
use DeskPRO\ImporterTools\Helpers\WriteHelper;
use DeskPRO\ImporterTools\Helpers\AttachmentHelper;

//--------------------
// Setup
//--------------------

$reader = ZenDeskReader::createReader($CONFIG['account']);
$output = OutputHelper::getHelper();
$writer = WriteHelper::getHelper();
$loader = AttachmentHelper::getHelper();

//--------------------
// Custom definitions
//--------------------

$customDefMapper = function(array $data) {
    $customDef = [
        'title'           => isset($data['title_in_portal']) && $data['title_in_portal'] ? $data['title_in_portal'] : $data['title'],
        'description'     => $data['description'],
        'is_enabled'      => $data['active'],
        'is_user_enabled' => $data['active'],
        'is_agent_field'  => isset($data['visible_in_portal']) && !$data['visible_in_portal'],
        'widget_type'     => ZenDeskMapper::$customFieldWidgetTypeMapping[$data['type']],
    ];

    foreach (['system_field_options', 'custom_field_options'] as $optionGroup) {
        if (!empty($data[$optionGroup])) {
            foreach ($data[$optionGroup] as $option) {
                $customDef['choices'][] = [
                    'title' => $option['value'],
                ];
            }
        }
    }

    return $customDef;
};

$output->startSection('Organization custom definitions');
foreach ($reader->getOrganizationFields() as $n) {
    $writer->writeOrganizationCustomDef($n['id'], $customDefMapper($n));
}

$output->startSection('Person custom definitions');
foreach ($reader->getPersonFields() as $n) {
    $writer->writePersonCustomDef($n['id'], $customDefMapper($n));
}

$output->startSection('Ticket custom definitions');
$ticketCustomDefMap = [];
foreach ($reader->getTicketFields() as $n) {
    $customDef                    = $customDefMapper($n);
    $ticketCustomDefMap[$n['id']] = $customDef['title'];

    $writer->writeTicketCustomDef($n['id'], $customDef);
}

//--------------------
// Organizations
//--------------------

$output->startSection('Organizations');
foreach ($reader->getOrganizations() as $n) {
    $organization = [
        'name'         => $n['name'],
        'date_created' => $n['created_at'],
        'labels'       => $n['tags'],
    ];

    // custom fields
    foreach ($n['organization_fields'] as $c) {
        if (!isset($c['value']) || !$c['value']) {
            continue;
        }

        $organization['custom_fields'][] = [
            'oid'   => $c['id'],
            'value' => $c['value'],
        ];
    }

    $writer->writeOrganization($n['id'], $organization);
}

//--------------------
// People
//--------------------

$writePerson = function(array $n) use($writer, $output) {
    // user could have empty email, add auto generated one
    if (!$n['email']) {
        $n['email'] = 'imported.user.'.$n['id'].'@example.com';
    }

    $person = [
        'name'         => $n['name'],
        'emails'       => [$n['email']],
        'date_created' => $n['created_at'],
        'organization' => $n['organization_id'],
    ];

    if (isset(ZenDeskMapper::$timezoneMapping[$n['time_zone']])) {
        $person['timezone'] = ZenDeskMapper::$timezoneMapping[$n['time_zone']];
    } else {
        $person['timezone'] = 'UTC';
        $output->warning("Found unknown timezone `{$n['time_zone']}`");
    }

    // custom fields
    foreach ($n['user_fields'] as $c) {
        if (!isset($c['value']) || !$c['value']) {
            continue;
        }

        $person['custom_fields'][] = [
            'oid'   => $c['id'],
            'value' => $c['value'],
        ];
    }

    if ($n['role'] === 'admin') {
        $person['is_admin'] = true;
        $writer->writeAgent($n['id'], $person, false);
    } elseif ($n['role'] === 'agent') {
        $writer->writeAgent($n['id'], $person, false);
    } else {
        $writer->writeUser($n['id'], $person, false);
    }
};

$output->startSection('People');
$pager = $reader->getPersonPager(new \DateTime($CONFIG['start_time']));

foreach ($pager as $n) {
    $writePerson($n);
}

//--------------------
// Tickets
//--------------------

$output->startSection('Tickets');
$statusMapping = [
    'new'     => 'awaiting_agent',
    'open'    => 'awaiting_agent',
    'pending' => 'awaiting_user',
    'hold'    => 'awaiting_agent',
    'solved'  => 'resolved',
    'closed'  => 'resolved',
    'deleted' => 'hidden.deleted',
];

$pager = $reader->getTicketPager(new \DateTime($CONFIG['start_time']));
foreach ($pager as $n) {
    $ticket = [
        'subject'      => $n['subject'] ?: 'No Subject',
        'status'       => $statusMapping[$n['status']],
        'person'       => $n['requester_id'],
        'agent'        => $n['assignee_id'],
        'labels'       => $n['tags'],
        'organization' => $n['organization_id'],
        'date_created' => $n['created_at'],
        'participants' => $n['collaborator_ids'],
    ];

    // custom fields
    foreach ($n['custom_fields'] as $c) {
        if (!$c['value']) {
            continue;
        }

        if (!empty($CONFIG['ticket_brand_field'])
            && isset($ticketCustomDefMap[$c['id']])
            && $ticketCustomDefMap[$c['id']] === $CONFIG['ticket_brand_field']) {
            $ticket['brand'] = $c['value'];
        } else {
            $ticket['custom_fields'][] = [
                'oid'   => $c['id'],
                'value' => $c['value'],
            ];
        }
    }

    // messages
    foreach ($reader->getTicketComments($n['id']) as $c) {
        $message = [
            'oid'          => $c['id'],
            'person'       => $c['author_id'],
            'message'      => $c['html_body'],
            'is_note'      => !$c['public'],
            'date_created' => $n['created_at'],
        ];

        foreach ($c['attachments'] as $a) {
            $blobData = $loader->loadAttachment($a['content_url']);
            if (!$blobData) {
                continue;
            }

            $message['attachments'][] = [
                'oid'          => $a['id'],
                'file_name'    => $a['file_name'],
                'content_type' => $a['content_type'],
                'blob_data'    => $blobData,
            ];
        }

        $ticket['messages'][] = $message;
    }

    $writer->writeTicket($n['id'], $ticket);
}

//--------------------
// Article categories
//--------------------

$output->startSection('Article categories');

$sections = [];
foreach ($reader->getArticlesSections() as $n) {
    $accessPolicy = $n['access_policy'];

    $sections[$n['category_id']][] = [
        'oid'         => $n['id'],
        'title'       => $n['name'],
        'user_groups' => $accessPolicy['viewable_by'] === 'everybody' ? ['everyone'] : ['registered'],
        'is_agent'    => $accessPolicy['viewable_by'] === 'staff',
    ];
}

foreach ($reader->getArticlesCategories() as $n) {
    $category = [
        'title'      => $n['name'],
        'categories' => isset($sections[$n['id']]) ? $sections[$n['id']] : [],
    ];

    $writer->writeArticleCategory($n['id'], $category);
}

//--------------------
// Articles
//--------------------

$output->startSection('Articles');
$pager = $reader->getArticlePager(new \DateTime($CONFIG['start_time']));

foreach ($pager as $n) {
    $article = [
        'person'       => $n['author_id'],
        'title'        => $n['title'],
        'content'      => $n['body'],
        'categories'   => [$n['section_id']],
        'labels'       => $n['label_names'],
        'date_created' => $n['created_at'],
        'date_updated' => $n['updated_at'],
        'language'     => ZenDeskMapper::getLanguageByLocale([$n['locale']]),
        'status'       => $n['draft'] ? 'hidden.draft' : 'published',
    ];

    // comments
    foreach ($reader->getArticleComments($n['id']) as $c) {
        $article['comments'][] = [
            'oid'          => $c['id'],
            'content'      => $c['body'],
            'person'       => $c['author_id'],
            'date_created' => $c['created_at'],
            'status'       => 'visible',
        ];
    }

    // attachments
    foreach ($reader->getArticleAttachments($n['id']) as $a) {
        $blobData = $loader->loadAttachment($a['content_url']);
        if (!$blobData) {
            continue;
        }

        $article['attachments'][] = [
            'oid'          => $a['id'],
            'file_name'    => $a['file_name'],
            'content_type' => $a['content_type'],
            'blob_data'    => $blobData,
        ];
    }

    // translations
    foreach ($reader->getArticleTranslations($n['id']) as $t) {
        $language = ZenDeskMapper::getLanguageByLocale([$t['locale']]);
        if (!$language) {
            continue;
        }

        $article['title_translations'][] = [
            'language' => $language,
            'value'    => $t['title'],
        ];

        $article['content_translations'][] = [
            'language' => $language,
            'value'    => $t['title'],
        ];
    }

    if ($n['draft']) {
        $article['status'] = 'hidden.draft';
    } else {
        $article['status'] = 'published';
    }

    $writer->writeArticle($n['id'], $article);
}
