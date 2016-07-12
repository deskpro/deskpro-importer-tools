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
 *     $ cp kayako.php /path/to/deskpro/bin
 *
 * 2) Run the import process to fetch all of your data from Kayako:
 *
 *     $ cd /path/to/deskpro
 *     $ bin/console import kayako.php
 *
 * 3) You can now optionally verify the integrity of your data:
 * 
 *     $ bin/console import:verify
 *
 * 4) When you're ready, go ahead and apply the import to your live database:
 *
 *     $ bin/console import:apply
 *
 * 4) And finally, you can clean up the temporary data files from the fileystem:
 *
 *     $ bin/console import:clean
 * 
 */

########################################################################################################################
# CONFIG
########################################################################################################################

$CONFIG = array();

/**
 * Enter the database connection details to your Kayako database.
 */
$CONFIG['dbinfo'] = [
    'host'     => 'localhost',
    'port'     => '3306',
    'user'     => 'my_user',
    'password' => 'my_password',
    'dbname'   => 'kayako'
];


########################################################################################################################
# Do not edit below this line
########################################################################################################################

//--------------------
// Setup
//--------------------

$writer = $importer->getWriter();
$output = $importer->getOutput();
$db     = $importer->getDbConnection($CONFIG['dbinfo']);

//--------------------
// Tickets and messages
//--------------------

$output->section("Tickets and Messages");

$pager = $importer->createPagerForQuery($db, "
    SELECT *
    FROM tickets
    ORDER BY id ASC
");

$output->startProgressForPager($pager);

while ($rawTickets = $pager->next()) {
    foreach ($rawTickets as $t) {
        $ticket = [
            'id'            => $t['id'],
            'ref'           => 'ticket-' . $t['id'],
            'department'    => $t['xx'],
            'person'        => $t['xx'],
            'agent'         => $t['xx'] ?: null,
            'status'        => 'awaiting_agent',
            'date_created'  => $t['xx'],
            'date_resolved' => $t['xx'] ?: null,
            'subject'       => $t['subject'],
            'messages'      => [],
        ];

        $rawMessages = $db->fetchAll("
            SELECT *
            FROM ticket_messages
            WHERE ticket_id = :ticketId
            ORDER BY id ASC
        ", ['ticket_id' => $t['id']]);

        foreach ($rawMessages as $m) {
            $ticket['messages'][] = [
                'id'           => $m['xx'],
                'message_text' => $m['xx'],
                'date_created' => $m['xx'],
                'is_note'      => $m['xx'],
                'person'       => $m['xx']
            ];
        }

        $writer->ticket($ticket);

        $output->advancePager();
    }
}

$output->finishSection();

//--------------------
// Users
//--------------------

$output->section("Users");

$pager = $importer->createPagerForQuery($db, "
    SELECT *
    FROM users
    ORDER BY id ASC
");

$output->startProgressForPager($pager);

while ($rawUsers = $pager->next()) {
    foreach ($rawUsers as $u) {
        $user = [
            'id'         => $u['id'],
            'email'      => $u['email'],
            'first_name' => $u['first_name'],
            'last_name'  => $u['last_name']
        ];

        $writer->user($user);

        $output->advancePager();
    }
}

$output->finishSection();

$output->finishProcess();
