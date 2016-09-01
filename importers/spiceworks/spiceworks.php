<?php
/*
 * This tool will connect to your SpiceWorks database and export your data into the data/ directory in
 * the standard DeskPRO Import Format.
 *
 * After this tool completes, you will run the standard DeskPRO import process to save the data to
 * your live helpdesk. Refer to the README for more details.
 *
 * 1) Edit the values in the CONFIG section below.
 *
 * 2) Run this script from the command-line:
 *
 *     $ cd /path/to/deskpro-importer-tools
 *     $ php spiceworks.php
 *
 * 3) Run the DeskPRO import script
 *     $ cd /path/to/deskpro
 *     $ php cmd.php dp:import:run json --input-path "/path/to/deskpro-importer-tools/data"
 */

########################################################################################################################
# CONFIG
########################################################################################################################

require __DIR__.'/config.php';

########################################################################################################################
# Do not edit below this line
########################################################################################################################

//--------------------
// Check config
//--------------------

if (empty($CONFIG['db_path']) || !is_file($CONFIG['db_path']) || !is_readable($CONFIG['db_path'])) {
    echo "Invalid database file: {$CONFIG['db_path']}\n";
    exit(1);
}

if (empty($CONFIG['ticket_attachments_path']) || !is_dir($CONFIG['ticket_attachments_path']) || !is_readable($CONFIG['ticket_attachments_path'])) {
    echo "Invalid attachments path: {$CONFIG['ticket_attachments_path']}\n";
    exit(1);
}

$CONFIG['ticket_attachments_path'] = rtrim($CONFIG['ticket_attachments_path'], '/\\');

if (!extension_loaded('pdo_sqlite')) {
    echo "This tool requires pdo_sqlite to be installed: http://php.net/manual/en/ref.pdo-sqlite.php\n";
    exit(1);
}

//--------------------
// Setup
//--------------------

use DeskPRO\ImporterTools\Helpers\OutputHelper;
use DeskPRO\ImporterTools\Helpers\WriteHelper;
use DeskPRO\ImporterTools\Helpers\FormatHelper;
use DeskPRO\ImporterTools\Helpers\DbHelper;

$output    = OutputHelper::getHelper();
$writer    = WriteHelper::getHelper();
$formatter = FormatHelper::getHelper();

$db = DbHelper::getHelper();
$db->setCredentials([
    'path'   => $CONFIG['db_path'],
    'driver' => 'pdo_sqlite',
]);

//--------------------
// Users
//--------------------

$output->startSection('Users');
$pager = $db->getPager(<<<SQL
    SELECT
        users.id, users.first_name, users.last_name, users.email, users.role
    FROM users
    ORDER BY users.id ASC
SQL
);

foreach ($pager as $n) {
    if (!$formatter->isEmailValid($n['email'])) {
        continue;
    }

    $person = [
        'emails'     => [$n['email']],
        'first_name' => $n['first_name'],
        'last_name'  => $n['last_name'],
        'name'       => trim($n['first_name'] . $n['last_name']) ?: '[no name]',
    ];

    if ($n['role'] === 'admin') {
        $writer->writeAgent($n['id'], $person, false);
    } else {
        $writer->writeUser($n['id'], $person, false);
    }
}

//--------------------
// Tickets and messages
//--------------------

$output->startSection('Tickets');
$pager = $db->getPager(<<<SQL
    SELECT
        t.id, t.summary, t.description, t.status,
        t.priority, t.created_at, t.updated_at, t.closed_at,
        t.created_by, t.assigned_to, t.category, t.status_updated_at,
        t.created_by, t.assigned_to
    FROM tickets AS t
    ORDER BY t.id ASC
SQL
);

foreach ($pager as $n) {
    $ticket = [
        'subject'       => $n['summary'],
        'person'        => $n['created_by'],
        'agent'         => $n['assigned_to'],
        'department'    => $n['category'],
        'status'        => $n['status'] === 'closed' ? 'resolved' : 'awaiting_agent',
        'date_created'  => $formatter->getFormattedDate($n['created_at']),
        'date_resolved' => $n['closed_at'] ? $formatter->getFormattedDate($n['closed_at']) : null,
    ];

    // first message from the ticket data
    $ticket['messages'][] = [
        'oid'          => 't'.$n['id'],
        'person'       => $n['created_by'],
        'message'      => $n['description'] ?: '(empty message)',
        'date_created' => $formatter->getFormattedDate($n['created_at']),
        'is_note'      => false,

    ];

    // messages
    $messagePager = $db->getPager(<<<SQL
        SELECT
            comments.id, comments.body, comments.created_at, comments.is_public,
            comments.attachment_content_type, comments.attachment_name,
            comments.created_by
        FROM comments
        WHERE comments.ticket_id = :ticket_id
        ORDER BY comments.id ASC
SQL
    , ['ticket_id' => $n['id']]);

    foreach ($messagePager as $m) {
        $message = [
            'oid'          => $m['id'],
            'message'      => $m['body'] ?: '(empty message)',
            'date_created' => $formatter->getFormattedDate($m['created_at']),
            'is_note'      => $m['is_public'] === 't',
            'person'       => $m['created_by']
        ];

        if ($m['attachment_name']) {
            $filePath = rtrim($CONFIG['ticket_attachments_path'], '/') .'/'.$n['id'].'/'.$m['id'].'-'.$m['attachment_name'];
            if (file_exists($filePath)) {
                $message['attachments'] = [[
                    'person'       => $m['created_by'],
                    'blob_path'    => $filePath,
                    'file_name'    => $m['attachment_name'],
                    'content_type' => $m['attachment_content_type']
                ]];
            } else {
                $output->warning("Missing file attachment on ticket {$n['id']}: {$m['attachment_name']} -- Expected: $filePath");
            }
        }

        $ticket['messages'][] = $message;
    }

    $writer->writeTicket($n['id'], $ticket);
}
