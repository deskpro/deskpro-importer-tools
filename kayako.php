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
 * 2) Run this script from the command-line:
 *
 *     $ cd /path/to/deskpro-importer-tools
 *     $ php kayako.php
 *
 * 3) Run the DeskPRO import script
 *     $ cd /path/to/deskpro
 *     $ bin/console dp:import:run json --input-path "/path/to/deskpro-importer-tools/data"
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

require 'src/inc.php';

// TODO verify config here and show friendly error messages if incorrect

//--------------------
// Setup
//--------------------

$writer = create_writer();
$writer->enableBatchedMode();

// TODO proper pdo connection
$db = new \PDO();

$per_page = 500;

//--------------------
// Tickets and messages
//--------------------

$ticket_query = $db->prepare("
    # TODO proper query
") or die(implode(', ', $db->errorInfo()));

$message_query = $db->prepare("
  # TODO proper query
") or die(implode(', ', $db->errorInfo()));

$start = 0;
$offset = 0;
$count = 0;

echo "Exporting tickets and messages ";

do {
    $offset = $start * $per_page;
    $start++;

    $ticket_query->execute(array($offset)) or die(implode(', ', $db->errorInfo()));
    $raw_tickets = $ticket_query->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($raw_tickets as $t) {
        $message_query->execute(array($t['id']));
        $raw_messages = $message_query->fetchAll(\PDO::FETCH_ASSOC);

        $messages = array();
        foreach ($raw_messages as $m) {
            $msg = array(
                'id'           => $m['xx'],
                'message_text' => $m['xx'],
                'date_created' => $m['xx'],
                'is_note'      => $m['xx'],
                'person'       => $m['xx']
            );

            $messages[] = $msg;
        }

        $ticket = array(
            'id'            => $t['id'],
            'ref'           => 'ticket-' . $t['id'],
            'department'    => $t['xx'],
            'person'        => $t['xx'],
            'agent'         => $t['xx'] ?: null,
            'status'        => 'awaiting_agent',
            'date_created'  => $t['xx'],
            'date_resolved' => $t['xx'] ?: null,
            'subject'       => $t['subject'],
            'messages'      => $messages,
        );

        $writer->ticket($ticket);

        echo ".";
        $count++;
    }
    if ($raw_tickets) {
        echo $count;
    }
} while($raw_tickets);

echo "\n\n";

//--------------------
// Users
//--------------------

$start = 0;

$user_query = $db->prepare("
  # TODO proper query
");

$start = 0;
$offset = 0;
$count = 0;

echo "Exporting users ";

do {
    $offset = $start * $per_page;
    $start++;

    $user_query->execute(array($offset)) or die(implode(', ', $db->errorInfo()));
    $raw_users = $user_query->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($raw_users as $u) {
        $writer->person(array(
            'id'         => $u['id'],
            'emails'     => array($u['xx']),
            'first_name' => $u['xx'],
            'last_name'  => $u['xx'],
            'is_agent'   => $u['xx'],
            'is_admin'   => $u['xx'],
        ), true);
        echo ".";
        $count++;
    }
    if ($raw_users) {
        echo $count;
    }
} while($raw_users);

echo "\n\n";

echo "All done.\n";