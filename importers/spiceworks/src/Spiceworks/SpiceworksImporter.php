<?php

namespace DeskPRO\ImporterTools\Importers\Spiceworks;

use DeskPRO\ImporterTools\AbstractImporter;
use DeskPRO\ImporterTools\Exceptions\PagerException;

/**
 * Class SpiceworksImporter.
 */
class SpiceworksImporter extends AbstractImporter
{
    /**
     * @var string
     */
    private $attachmentsPath;

    /**
     * {@inheritdoc}
     */
    public function init(array $config)
    {
        if (empty($config['db_path']) || !is_file($config['db_path']) || !is_readable($config['db_path'])) {
            throw new \RuntimeException("Invalid database file: {$config['db_path']}");
        }
        if (empty($config['ticket_attachments_path']) || !is_dir($config['ticket_attachments_path']) || !is_readable($config['ticket_attachments_path'])) {
            throw new \RuntimeException("Invalid attachments path: {$config['ticket_attachments_path']}");
        }
        if (!extension_loaded('pdo_sqlite')) {
            throw new \RuntimeException("This tool requires pdo_sqlite to be installed: http://php.net/manual/en/ref.pdo-sqlite.php");
        }

        $this->attachmentsPath = rtrim($config['ticket_attachments_path'], '/\\');
        $this->db()->setCredentials([
            'path'   => $config['db_path'],
            'driver' => 'pdo_sqlite',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function testConfig()
    {
        // try to make a db request to make sure that provided credentials are correct
        $this->db()->getPager('SELECT COUNT(*) FROM users');
    }

    /**
     * {@inheritdoc}
     */
    protected function getImportSteps()
    {
        return [
            'person',
            'ticket',
        ];
    }

    //---------------------
    // Import step handlers
    //---------------------

    /**
     * @param int $offset
     *
     * @return void
     */
    protected function personImport($offset)
    {
        $this->progress()->startPersonImport();
        $pager = $this->db()->getPager(
            $this->currentStep,
            <<<SQL
                SELECT
                    users.id, users.first_name, users.last_name, users.email, users.role
                FROM users
                ORDER BY users.id ASC
SQL
        ,
            [],
            $offset
        );

        foreach ($pager as $n) {
            if (!$this->formatter()->isEmailValid($n['email'])) {
                continue;
            }

            $person = [
                'emails'     => [$n['email']],
                'first_name' => $n['first_name'],
                'last_name'  => $n['last_name'],
                'name'       => trim($n['first_name'] . $n['last_name']) ?: '[no name]',
            ];

            if ($n['role'] === 'admin') {
                $this->writer()->writeAgent($n['id'], $person, false);
            } else {
                $this->writer()->writeUser($n['id'], $person, false);
            }
        }
    }

    /**
     * @param int $offset
     *
     * @return void
     */
    protected function ticketImport($offset)
    {
        $this->progress()->startTicketImport();
        $pager = $this->db()->getPager(
            $this->currentStep,
            <<<SQL
                SELECT
                    t.id, t.summary, t.description, t.status,
                    t.priority, t.created_at, t.updated_at, t.closed_at,
                    t.created_by, t.assigned_to, t.category, t.status_updated_at,
                    t.created_by, t.assigned_to
                FROM tickets AS t
                ORDER BY t.id ASC
SQL
        ,
            [],
            $offset
        );

        foreach ($pager as $n) {
            $ticket = [
                'subject'       => $n['summary'],
                'person'        => $n['created_by'],
                'agent'         => $n['assigned_to'],
                'department'    => $n['category'],
                'status'        => $n['status'] === 'closed' ? 'resolved' : 'awaiting_agent',
                'date_created'  => $this->formatter()->getFormattedDate($n['created_at']),
                'date_resolved' => $n['closed_at'] ? $this->formatter()->getFormattedDate($n['closed_at']) : null,
            ];

            // first message from the ticket data
            $ticket['messages'][] = [
                'oid'          => 't'.$n['id'],
                'person'       => $n['created_by'],
                'message'      => $n['description'] ?: '(empty message)',
                'date_created' => $this->formatter()->getFormattedDate($n['created_at']),
                'is_note'      => false,

            ];

            // messages
            $messagePager = $this->db()->getPager(
                'ticket_messages',
                <<<SQL
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
                    'date_created' => $this->formatter()->getFormattedDate($m['created_at']),
                    'is_note'      => $m['is_public'] === 't',
                    'person'       => $m['created_by']
                ];

                if ($m['attachment_name']) {
                    $filePath = rtrim($this->attachmentsPath , '/') .'/'.$n['id'].'/'.$m['id'].'-'.$m['attachment_name'];
                    if (file_exists($filePath)) {
                        $message['attachments'] = [[
                            'person'       => $m['created_by'],
                            'blob_path'    => $filePath,
                            'file_name'    => $m['attachment_name'],
                            'content_type' => $m['attachment_content_type']
                        ]];
                    } else {
                        $this->output()
                            ->warning("Missing file attachment on ticket {$n['id']}: {$m['attachment_name']} -- Expected: $filePath");
                    }
                }

                $ticket['messages'][] = $message;
            }

            $this->writer()->writeTicket($n['id'], $ticket);
        }
    }
}
