<?php
require 'src/inc.php';

$writer = create_writer();

// TODO
// Your code goes here.
//
// Use PHP to read data from your custom source and write it into
// files that the DeskPRO importer can understand.
//
// Check out the other scripts in this directory for examples.

// ---------------------------------------------------------------------------------------------------------
// EXAMPLE
// ---------------------------------------------------------------------------------------------------------
// Here's an example that connects to a MySQL database, and downloads tickets, messages, and user data:
// ---------------------------------------------------------------------------------------------------------

$db = new PDO(
	'mysql:host=localhost;dbname=my_database',
	'my_user',
	'my_password'
);

$per_page = 500;

// Example query to fetch ticket data
$ticket_query = $db->prepare("
  SELECT t.*, u.email AS user_email
  FROM tickets AS t
  LEFT JOIN users u ON (u.id = t.user_id)
  ORDER BY t.id ASC
  LIMIT ?, $per_page
") or die(implode(', ', $db->errorInfo()));

// Example query to fetch messages in a ticket
$message_query = $db->prepare("
  SELECT m.*, , u.email AS user_email
  FROM messages AS m
  LEFT JOIN users u ON (u.id = m.user_id)
  WHERE m.ticket_id = ?
  ORDER BY m.id ASC
") or die(implode(', ', $db->errorInfo()));

// Example query to fetch user data
$user_query = $db->prepare("
  SELECT u.*
  FROM users AS u
  ORDER BY u.id ASC
  LIMIT ?, $per_page
");

/**
 * Given a raw ticket database record $t, return a standard DeskPRO ticket array.
 * 
 * @param  array  $t Raw database record
 * @return array  Standard DeskPRO array
 */
function get_ticket_data(array $t)
{
	return array(
        'id'            => $t['id'],
        'ref'           => 'ticket-' . $t['id'],
        'department'    => 'Imported Tickets',
        'person'        => $t['user_email'],
        'status'        => 'awaiting_agent',
        'date_created'  => $t['date_created'],
        'subject'       => $t['subject']
    );
}

/**
 * Given a raw message database record $t, return a standard DeskPRO message array.
 * 
 * @param  array  $m Raw database record
 * @return array  Standard DeskPRO array
 */
function get_message_data(array $m)
{
	return array(
        'id'           => $m['id'],
        'message_text' => $m['message'],
        'date_created' => $m['date_created'],
        'person'       => $m['user_email']
    );
}

/**
 * Given a raw user database record $t, return a standard DeskPRO user array.
 * 
 * @param  array  $u Raw database record
 * @return array  Standard DeskPRO array
 */
function get_user_data(array $u)
{
	return array(
        'id'         => $u['id'],
        'emails'     => array($u['email']),
        'first_name' => $u['first_name'],
        'last_name'  => $u['last_name']
    )
}

// ---------------------------------------------------------------------------------------------------------
// You probably don't need to edit anything below this line.

$start    = 0;
$offset   = 0;
$count    = 0;

echo "Exporting tickets and messages ";

do {
    $offset = $start * $per_page;
    $start++;

    $ticket_query->execute(array($offset)) or die(implode(', ', $db->errorInfo()));
    $raw_tickets = $ticket_query->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($raw_tickets as $t) {
        $message_query->execute(array($t['id']));
        $raw_messages = $message_query->fetchAll(\PDO::FETCH_ASSOC);

        $messages = [];
        foreach ($raw_messages as $m) {
            $messages[] = get_message_data($m);
        }

		$ticket = get_ticket_data($t);
		$ticket['messages'] = $messages;        

        $writer->ticket($ticket);

        echo ".";
        $count++;
    }
} while($raw_tickets);

echo "\n\n";

//--------------------
// Users
//--------------------

$start  = 0;
$start  = 0;
$offset = 0;
$count  = 0;

echo "Exporting users ";

do {
    $offset = $start * $per_page;
    $start++;

    $user_query->execute(array($offset)) or die(implode(', ', $db->errorInfo()));
    $raw_users = $user_query->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($raw_users as $u) {
        $writer->person(get_user_data($u), true);
        echo ".";
        $count++;
    }
} while($raw_users);

echo "\n\n";

echo "All done.\n";