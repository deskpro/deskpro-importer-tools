<?php

namespace DeskPRO\ImporterTools\Importers\Osticket;

use DeskPRO\ImporterTools\AbstractImporter;

/**
 * Class OsticketImporter.
 */
class OsticketImporter extends AbstractImporter
{
    /**
     * @var
     */
    private $tablePrefix;

    /**
     * @var array
     */
    private $widgetTypeMapping = [
        'text'     => 'text',
        'memo'     => 'textarea',
        'datetime' => 'datetime',
        'choices'  => 'choice',
        'bool'     => 'toggle',
    ];

    /**
     * {@inheritdoc}
     */
    public function init(array $config)
    {
        if (!isset($config['dbinfo'])) {
            throw new \RuntimeException('Importer config does not have `dbinfo` credentials.');
        }
        if (!isset($config['table_prefix'])) {
            throw new \RuntimeException('Importer config does not have `table_prefix`.');
        }

        $this->db()->setCredentials($config['dbinfo']);
        $this->tablePrefix = $config['table_prefix'];
    }

    /**
     * {@inheritdoc}
     */
    public function testConfig()
    {
        // try to make a db request to make sure that provided credentials are correct
        $this->db()->getPager("SELECT COUNT(*) FROM {$this->tablePrefix}organization");
    }

    /**
     * {@inheritdoc}
     */
    protected function getImportSteps()
    {
        return [
            'organization_custom_def',
            'person_custom_def',
            'ticket_custom_def',
            'organization',
            'person_staff',
            'person_user',
            'ticket',
            'article_category',
            'article',
            'setting',
        ];
    }

    //---------------------
    // Import step handlers
    //---------------------

    /**
     * @return void
     */
    protected function organizationCustomDefImport()
    {
        $this->progress()->startOrganizationCustomDefImport();

        foreach ($this->getFormFields('O') as $id => $formField) {
            $this->writer()->writeOrganizationCustomDef($id, $formField);
        }
    }

    /**
     * @return void
     */
    protected function personCustomDefImport()
    {
        $this->progress()->startPersonCustomDefImport();

        foreach ($this->getFormFields('U') as $id => $formField) {
            $this->writer()->writePersonCustomDef($id, $formField);
        }
    }

    /**
     * @return void
     */
    protected function ticketCustomDefImport()
    {
        $this->progress()->startTicketCustomDefImport();

        foreach ($this->getFormFields('T') as $id => $formField) {
            $this->writer()->writeTicketCustomDef($id, $formField);
        }
    }

    /**
     * @param int $offset
     *
     * @return void
     */
    protected function organizationImport($offset) {
        $this->progress()->startOrganizationImport();
        $pager = $this->db()->getPager(
            'organization',
            "SELECT * FROM {$this->tablePrefix}organization",
            [],
            $offset
        );

        foreach ($pager as $n) {
            $organization = [
                'name'         => $n['name'],
                'date_created' => $this->formatter()->getFormattedDate($n['created']),
            ];

            // custom and contact data
            $this->mapFormFields($n['id'], 'O', $organization);

            $this->writer()->writeOrganization($n['id'], $organization);
        }
    }

    /**
     * @param int $offset
     *
     * @return void
     */
    protected function personStaffImport($offset)
    {
        $this->progress()->startPersonImport();
        $pager = $this->db()->getPager(
            'person_staff',
            "SELECT * FROM {$this->tablePrefix}staff",
            [],
            $offset
        );

        $agentGroupNames = [];
        foreach ($this->db()->findAll("SELECT * FROM {$this->tablePrefix}groups") as $group) {
            $agentGroupNames[$group['group_id']] = $group['group_name'];
        }

        foreach ($pager as $n) {
            $agent = [
                'name'         => $n['firstname'].' '.$n['lastname'],
                'emails'       => [$n['email']],
                'is_admin'     => $n['isadmin'],
                'is_disabled'  => !$n['isactive'],
                'date_created' => $this->formatter()->getFormattedDate($n['created']),
            ];

            // agent group
            if ($n['group_id'] && isset($agentGroupNames[$n['group_id']])) {
                $agent['agent_groups'][] = $agentGroupNames[$n['group_id']];
            }

            // phone numbers
            if ($phone = $this->formatter()->getFormattedNumber($n['phone'])) {
                $agent['contact_data']['phone'][] = [
                    'number' => $phone,
                    'type'   => 'phone',
                ];
            }
            if ($mobile = $this->formatter()->getFormattedNumber($n['mobile'])) {
                $agent['contact_data']['phone'][] = [
                    'number' => $mobile,
                    'type'   => 'mobile',
                ];
            }

            $this->writer()->writeAgent($n['staff_id'], $agent);
        }
    }

    /**
     * @param int $offset
     *
     * @return void
     */
    protected function personUserImport($offset)
    {
        $this->progress()->startPersonImport();

        $pager = $this->db()->getPager(
            'person_user',
            "SELECT * FROM {$this->tablePrefix}user",
            [],
            $offset
        );
        foreach ($pager as $n) {
            $user = [
                'name'         => $n['name'],
                'organization' => $n['org_id'],
                'date_created' => $this->formatter()->getFormattedDate($n['created']),
            ];

            // emails
            $userEmails = $this->db()
                ->findAll("SELECT * FROM {$this->tablePrefix}user_email WHERE user_id = :user_id", [
                    'user_id' => $n['id']
                ]);

            foreach ($userEmails as $email) {
                $user['emails'][] = $email['address'];
            }

            // custom and contact data
            $this->mapFormFields($n['id'], 'U', $user);

            $this->writer()->writeUser($n['id'], $user);
        }
    }

    /**
     * @param int $offset
     *
     * @return void
     * @throws \Exception
     */
    protected function ticketImport($offset)
    {
        $this->progress()->startTicketImport();
        $pager = $this->db()->getPager(
            'ticket',
            "SELECT * FROM {$this->tablePrefix}ticket",
            [],
            $offset
        );

        // ticket statuses
        $ticketStatusesMap = [
            'open'     => 'awaiting_agent',
            'closed'   => 'resolved',
            'archived' => 'archived',
            'deleted'  => 'hidden.deleted',
        ];

        $ticketStatusesIdMap = [];
        foreach ($this->db()->findAll("SELECT * FROM {$this->tablePrefix}ticket_status") as $ticketStatus) {
            if (isset($ticketStatusesMap[$ticketStatus['state']])) {
                $ticketStatusesIdMap[$ticketStatus['id']] = $ticketStatusesMap[$ticketStatus['state']];
            } else {
                $ticketStatusesIdMap[$ticketStatus['id']] = 'awaiting_agent';
            }
        }

        // ticket departments
        $ticketDepartmentsMap = [];
        foreach ($this->db()->findAll("SELECT * FROM {$this->tablePrefix}department") as $department) {
            $ticketDepartmentsMap[$department['dept_id']] = $department['dept_name'];
        }

        foreach ($pager as $n) {
            $ticket = [
                'person' => $this->writer()->userOid($n['user_id']),
                'agent'  => $this->writer()->agentOid($n['staff_id']),
            ];

            // ticket status
            if (isset($ticketStatusesIdMap[$n['status_id']])) {
                $ticket['status'] = $ticketStatusesIdMap[$n['status_id']];
            } else {
                $ticket['status'] = 'awaiting_agent';
            }

            // ticket department
            if (isset($ticketDepartmentsMap[$n['dept_id']])) {
                $ticket['department'] = $ticketDepartmentsMap[$n['dept_id']];
            }

            // custom and contact data
            $this->mapFormFields($n['ticket_id'], 'T', $ticket);

            // messages
            $messagesPager = $this->db()
                ->getPager(
                    'ticket_messages',
                    "SELECT * FROM {$this->tablePrefix}ticket_thread WHERE ticket_id = :ticket_id",
                    ['ticket_id' => $n['ticket_id']]
                );

            foreach ($messagesPager as $m) {
                $message = [
                    'oid'     => $m['id'],
                    'person'  => $this->writer()->userOid($m['user_id']),
                    'message' => $m['body'],
                    'is_note' => $m['thread_type'] === 'N',
                ];

                // message attachments
                $messageAttachments = $this->db()
                    ->findAll("SELECT * FROM {$this->tablePrefix}ticket_attachment WHERE ref_id = :message_id", [
                        'message_id' => $m['id'],
                    ]);

                foreach ($messageAttachments as $attachment) {
                    $blobData = $this->getBlobData($attachment['file_id']);
                    if ($blobData) {
                        $message['attachments'][] = $blobData;
                    }
                }

                $ticket['messages'][] = $message;
            }

            $this->writer()->writeTicket($n['ticket_id'], $ticket);
        }
    }

    /**
     * @param int $offset
     *
     * @return void
     */
    protected function articleCategoryImport($offset)
    {
        $this->progress()->startArticleCategoryImport();
        $pager = $this->db()->getPager(
            'article_category',
            "SELECT * FROM {$this->tablePrefix}faq_category",
            [],
            $offset
        );

        foreach ($pager as $n) {
            $this->writer()->writeArticleCategory($n['category_id'], [
                'title'    => $n['name'],
                'is_agent' => !$n['ispublic'],
            ]);
        }
    }

    /**
     * @param int $offset
     *
     * @return void
     */
    protected function articleImport($offset)
    {
        $this->progress()->startArticleImport();
        $pager = $this->db()->getPager(
            'article',
            "SELECT * FROM {$this->tablePrefix}faq",
            [],
            $offset
        );

        foreach ($pager as $n) {
            $this->writer()->writeArticle($n['faq_id'], [
                'title'       => $n['question'],
                'content'     => $n['answer'],
                'categories'  => [$n['category_id']],
                'status'      => $n['ispublished'] ? 'published' : 'hidden.unpublished',
                'attachments' => $this->getAttachments($n['faq_id'], 'F'),
            ]);
        }
    }

    /**
     * @param int $offset
     *
     * @return void
     */
    protected function settingImport($offset)
    {
        $this->progress()->startSettingImport();
        $settingMapping = [
            'helpdesk_url'   => 'core.deskpro_url',
            'helpdesk_title' => 'core.deskpro_name',
        ];

        $pager = $this->db()
            ->getPager(
                'setting',
                "SELECT * FROM {$this->tablePrefix}config WHERE namespace = 'core' AND `key` IN (:setting_names)",
                [
                    'section'       => 'settings',
                    'setting_names' => array_keys($settingMapping),
                ],
                $offset
            );

        foreach ($pager as $n) {
            $this->writer()->writeSetting($n['id'], [
                'name'  => $settingMapping[$n['key']],
                'value' => $n['value'],
            ]);
        }
    }

    //--------------------
    // Helpers
    //--------------------

    /**
     * @param string|int $fileId
     *
     * @return bool|array
     */
    protected function getBlobData($fileId)
    {
        $file = $this->db()->findOne("SELECT * FROM {$this->tablePrefix}file WHERE id = :id", ['id' => $fileId]);
        if (!$file) {
            return false;
        }

        $fileChunks = $this->db()->findAll("SELECT * FROM {$this->tablePrefix}file_chunk WHERE file_id = :file_id", [
            'file_id' => $file['id'],
        ]);

        $blobData = '';
        foreach ($fileChunks as $fileChunk) {
            $blobData .= $fileChunk['filedata'];
        }

        if (!$blobData) {
            return false;
        }

        return [
            'oid'          => $file['id'],
            'file_name'    => $file['name'],
            'content_type' => $file['type'],
            'blob_data'    => base64_encode($blobData),
        ];
    }

    /**
     * @param int|string $id
     * @param string     $type
     *
     * @return array
     */
    protected function getAttachments($id, $type)
    {
        $data = $this->db()
            ->findAll("SELECT * FROM {$this->tablePrefix}attachment WHERE type = :type AND object_id = :id", [
                'id'   => $id,
                'type' => $type,
            ]);

        $attachments = [];
        foreach ($data as $attachment) {
            $blobData = $this->getBlobData($attachment['file_id']);
            if ($blobData) {
                $attachments[] = $blobData;
            }
        }

        return $attachments;
    }

    /**
     * @param int|string $id
     * @param string     $type
     * @param array      $object
     *
     * @return void
     */
    protected function mapFormFields($id, $type, array &$object)
    {
        // custom and contact data
        $data = $this->db()->findAll(<<<SQL
                SELECT f.id, v.value, f.type, f.name, f.label
                FROM {$this->tablePrefix}form_entry_values v
                JOIN {$this->tablePrefix}form_entry e ON v.entry_id = e.id
                JOIN {$this->tablePrefix}form_field f ON v.field_id = f.id
                WHERE e.object_type = :type AND e.object_id = :id
SQL
            , [
                'id'   => $id,
                'type' => $type,
            ]
        );

        foreach ($data as $field) {
            $value = $field['value'];
            if (!$value) {
                continue;
            }

            switch ($field['type']) {
                case 'text':
                    if ($field['name'] === 'website') {
                        if ($websiteUrl = $this->formatter()->getFormattedUrl($value)) {
                            $object['contact_data']['website'][] = [
                                'url' => $websiteUrl,
                            ];
                        }
                        // built-in ticket field for subject
                    } elseif ($field['name'] === 'subject' && $type === 'T') {
                        $object['subject'] = $value;
                    } else {
                        $object['custom_fields'][] = [
                            'name'  => $field['label'],
                            'value' => $value,
                        ];
                    }
                    break;
                case 'memo':
                    if ($field['name'] === 'address') {
                        // todo
                    } else {
                        $object['custom_fields'][] = [
                            'oid'   => $field['id'],
                            'value' => $value,
                        ];
                    }
                    break;
                case 'choices':
                    $value = json_decode($value, true);
                    $value = reset($value);

                    $object['custom_fields'][] = [
                        'oid'   => $field['id'],
                        'value' => $value,
                    ];
                    break;
                case 'phone':
                    if ($phone = $this->formatter()->getFormattedNumber($value)) {
                        $object['contact_data']['phone'][] = [
                            'number' => $phone,
                            'type'   => 'phone',
                        ];
                    }
                    break;
                case 'datetime':
                    $object['custom_fields'][] = [
                        'oid'   => $field['id'],
                        'value' => $this->formatter()->getFormattedDate($value),
                    ];
                    break;
                default:
                    $object['custom_fields'][] = [
                        'oid'   => $field['id'],
                        'value' => $value,
                    ];
                    break;
            }
        }
    }

    /**
     * @param string $type
     *
     * @return array
     */
    protected function getFormFields($type)
    {
        $data = $this->db()->findAll(
            <<<SQL
                SELECT ff.*
                FROM {$this->tablePrefix}form_field ff
                JOIN {$this->tablePrefix}form f ON ff.form_id = f.id
                WHERE f.type = :type
SQL
            ,
            [
                'type' => $type,
            ]
        );

        $customDefs = [];
        foreach ($data as $n) {
            // exclude contact data fields
            if ($n['type'] === 'phone') {
                continue;
            }
            if (in_array($n['name'], ['website', 'address'])) {
                continue;
            }

            // exclude ticket subject field
            if ($n['name'] === 'subject' && $type === 'T') {
                continue;
            }

            $widgetType = 'text';
            if (isset($this->widgetTypeMapping[$n['type']])) {
                $widgetType = $this->widgetTypeMapping[$n['type']];
            }

            $customDef = [
                'title'          => $n['label'],
                'widget_type'    => $widgetType,
                'is_agent_field' => $n['private'],
            ];

            if ($n['required']) {
                $customDef['options']['required'] = true;
            }

            // export field choices
            if ($widgetType === 'choice') {
                $configuration = json_decode($n['configuration'], true);
                if (isset($configuration['choices'])) {
                    $configuration['choices'] = preg_split('/\r\n/', $configuration['choices']);
                    foreach ($configuration['choices'] as $choice) {
                        $customDef['choices'][] = [
                            'title' => $choice,
                        ];
                    }
                }
            }

            $customDefs[$n['id']] = $customDef;
        }

        return $customDefs;
    }
}
