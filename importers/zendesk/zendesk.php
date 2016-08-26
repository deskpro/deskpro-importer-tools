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

//--------------------
// Setup
//--------------------

$reader = ZenDeskReader::createReader($CONFIG['account']);
$output = OutputHelper::getHelper();
$writer = WriteHelper::getHelper();

//--------------------
// Organizations
//--------------------

$output->startSection('Organizations');
foreach ($reader->getOrganizations() as $organization) {
    $writer->writeOrganization($organization['id'], [
        'name'         => $organization['name'],
        'date_created' => $organization['created_at'],
        'labels'       => $organization['tags'],
    ]);
}

//--------------------
// People
//--------------------

$output->startSection('People');
$pager = $reader->getPersonPager(new \DateTime($CONFIG['start_time']));

foreach ($pager as $n) {
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

    try {
        $person['timezone'] = ZenDeskMapper::$timezoneMapping[$n['time_zone']];
    } catch (\RuntimeException $e) {
        $person['timezone'] = 'UTC';
        $output->warning("Found unknown timezone `{$n['time_zone']}`");
    }

    if ($n['role'] === 'admin') {
        $person['is_admin'] = true;
        $writer->writeAgent($n['id'], $person, false);
    } elseif ($n['role'] === 'agent') {
        $writer->writeAgent($n['id'], $person, false);
    } else {
        $writer->writeUser($n['id'], $person, false);
    }
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
    'closed'  => 'archived',
    'deleted' => 'hidden.deleted',
];

$pager = $reader->getTicketPager(new \DateTime($CONFIG['start_time']));
foreach ($pager as $n) {
    $ticket = [
        'subject' => $n['subject'],
        'status'  => $statusMapping[$n['status']],
        'user'    => $n['requester_id'],
        'agent'   => $n['assignee_id'],
        'labels'  => $n['tags'],
    ];

    foreach ($reader->getTicketComments($n['id']) as $c) {
        $ticket['messages'][] = [
            'oid'     => $c['id'],
            'person'  => $c['author_id'],
            'message' => $c['body'],
            'is_note' => !$c['public'],
        ];
    }

    $writer->writeTicket($n['id'], $ticket);
}


//--------------------
// Articles
//--------------------

$output->startSection('Articles');
$pager = $reader->getArticlePager(new \DateTime($CONFIG['start_time']));

foreach ($pager as $n) {
    $article = [
        'person'  => $n['author_id'],
        'title'   => $n['title'],
        'content' => $n['body'],
    ];

    if ($n['draft']) {
        $article['status'] = 'hidden.draft';
    } else {
        $article['status'] = 'published';
    }

    $writer->writeArticle($n['id'], $article);
}
