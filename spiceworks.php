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

$CONFIG = array();

/**
 * Enter the full path to your SpiceWorks SQLite database file.
 */
$CONFIG['db_path'] = '/path/to/spiceworks.db';

/**
 * Enter the full path to your SpiceWorks ticket attachments directory.
 * The data directory should be: <path to SpiceWorks>/data/uploads/Ticket
 *
 * This directory should contain many sub-directories. Each directory is
 * a ticket ID, and inside each directory will be the file attachments
 * on the ticket.
 */
$CONFIG['ticket_attachments_path'] = '/path/to/spiceworks/data/uploads/Ticket';





########################################################################################################################
# Do not edit below this line
########################################################################################################################

require 'src/inc.php';

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

// Special setting that can be set
// If set, this path will be used for attachments
// when the next step of the process is run.
if (empty($CONFIG['ticket_attachments_import_path'])) {
    $CONFIG['ticket_attachments_import_path'] = '';
}

$CONFIG['ticket_attachments_import_path'] = rtrim($CONFIG['ticket_attachments_import_path'], '/\\');

//--------------------
// Setup
//--------------------

$writer = create_writer();
$writer->enableBatchedMode();

$db = new \PDO("sqlite:{$CONFIG['db_path']}");

$per_page = 500;

//--------------------
// Tickets and messages
//--------------------

$ticket_query = $db->prepare("
  SELECT
    t.id, t.summary, t.description, t.status,
    t.priority, t.created_at, t.updated_at, t.closed_at,
    t.created_by, t.assigned_to, t.category, t.status_updated_at,
    t.created_by, t.assigned_to,
    creator.email as creator_email,
    assigned.email as assigned_email
  FROM tickets AS t
  LEFT JOIN users AS creator ON (creator.id = t.created_by)
  LEFT JOIN users AS assigned ON (assigned.id = t.assigned_to)
  ORDER BY t.id ASC
  LIMIT ?, $per_page
") or die(implode(', ', $db->errorInfo()));

$message_query = $db->prepare("
  SELECT
    comments.id, comments.body, comments.created_at, comments.is_public,
    comments.attachment_content_type, comments.attachment_name,
    comments.created_by,
    creator.email AS creator_email
  FROM comments
  LEFT JOIN users AS creator ON (creator.id = comments.created_by)
  WHERE comments.ticket_id = ?
  ORDER BY comments.id ASC
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

        if (empty($t['creator_email'])) {
            echo "No creator for {$t['id']}\n";
            continue;
        }

        $user_ids = array();
        if ($t['assigned_to']) {
            $user_ids[$t['assigned_to']] = true;
        }
        if ($t['created_by']) {
            $user_ids[$t['created_by']] = true;
        }

        $messages = array(array(
            'id'            => 't' . $t['id'],
            'message_text'  => $t['description'] ?: '(empty message)',
            'date_created'  => $t['created_at'],
            'is_note'       => false,
            'person'        => $t['creator_email'],
        ));
        foreach ($raw_messages as $m) {
            $msg = array(
                'id'           => $m['id'],
                'message_text' => $m['body'] ?: '(empty message)',
                'date_created' => $m['created_at'],
                'is_note'      => $m['is_public'] === 't',
                'person'       => $m['creator_email']
            );

            if ($m['created_by']) {
                $user_ids[$m['created_by']] = true;
            }

            if ($m['attachment_name']) {
                $path_part = DIRECTORY_SEPARATOR . $t['id'] . DIRECTORY_SEPARATOR . $m['id'] . '-' . $m['attachment_name'];
                $file_path = $CONFIG['ticket_attachments_path'] . $path_part;
                if (file_exists($file_path)) {
                    if ($CONFIG['ticket_attachments_import_path']) {
                        $use_file_path = $CONFIG['ticket_attachments_import_path'] . $path_part;
                    } else {
                        $use_file_path = $file_path;
                    }
                    $msg['attachments'] = array(array(
                        'person'       => $m['creator_email'],
                        'blob_path'    => $use_file_path,
                        'file_name'    => $m['attachment_name'],
                        'content_type' => $m['attachment_content_type']
                    ));
                } else {
                    echo "\nWarning: Missing file attachment on ticket {$t['id']}: {$m['attachment_name']} -- Expected: $file_path\n";
                }
            }

            $messages[] = $msg;
        }

        $status = $t['status'] === 'closed' ? 'resolved' : 'awaiting_agent';

        $ticket = array(
            'id'            => $t['id'],
            'ref'           => 'ticket-' . $t['id'],
            'department'    => $t['category'],
            'person'        => $t['creator_email'],
            'agent'         => $t['assigned_email'] ?: null,
            'status'        => $status,
            'date_created'  => $t['created_at'],
            'date_resolved' => $t['closed_at'] ?: null,
            'subject'       => $t['summary'],
            'messages'      => $messages,
        );

        $writer->ticket($ticket);

        // users for these tickets
        if ($user_ids) {
            $whereIn = str_repeat('?,', count($user_ids) - 1) . '?';
            $user_finder_query = $db->prepare("
              SELECT
                users.id, users.first_name, users.last_name, users.email, users.role
              FROM users WHERE id IN ($whereIn)
            ");
            $user_finder_query->execute(array_keys($user_ids));
            $users = $user_finder_query->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($users as $u) {
                $writer->person(array(
                    'id'         => $u['id'],
                    'emails'     => array($u['email']),
                    'first_name' => $u['first_name'],
                    'last_name'  => $u['last_name'],
                    'name'       => trim($u['first_name'] . $u['last_name']) ?: '[no name]',
                    'is_agent'   => $u['role'] === 'admin',
                    'is_admin'   => $u['role'] === 'admin',
                ), true);
                echo ".";
            }
        }

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
  SELECT
    users.id, users.first_name, users.last_name, users.email, users.role
  FROM users
  ORDER BY users.id ASC
  LIMIT $per_page OFFSET ?
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
            'emails'     => array($u['email']),
            'first_name' => $u['first_name'],
            'last_name'  => $u['last_name'],
            'name'       => trim($u['first_name'] . $u['last_name']) ?: '[no name]',
            'is_agent'   => $u['role'] === 'admin',
            'is_admin'   => $u['role'] === 'admin',
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