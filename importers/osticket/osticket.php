<?php

########################################################################################################################
# CONFIG
########################################################################################################################

require __DIR__.'/config.php';

########################################################################################################################
# Do not edit below this line
########################################################################################################################

use DeskPRO\ImporterTools\Helpers\OutputHelper;
use DeskPRO\ImporterTools\Helpers\WriteHelper;
use DeskPRO\ImporterTools\Helpers\FormatHelper;
use DeskPRO\ImporterTools\Helpers\DbHelper;

//--------------------
// Setup
//--------------------

$output    = OutputHelper::getHelper();
$writer    = WriteHelper::getHelper();
$formatter = FormatHelper::getHelper();

$db = DbHelper::getHelper();
$db->setCredentials($CONFIG['dbinfo']);

$tablePrefix = $CONFIG['table_prefix'];

//--------------------
// Attachments loader
//--------------------

$getBlobData = function ($fileId) use ($db, $tablePrefix) {
    $file = $db->findOne("SELECT * FROM {$tablePrefix}file WHERE id = :id", ['id' => $fileId]);
    if (!$file) {
        return false;
    }

    $fileChunks = $db->findAll("SELECT * FROM {$tablePrefix}file_chunk WHERE file_id = :file_id", [
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
};

$getAttachments = function ($id, $type) use ($db, $tablePrefix, $getBlobData) {
    $data = $db->findAll("SELECT * FROM {$tablePrefix}attachment WHERE type = :type AND object_id = :id", [
        'id'   => $id,
        'type' => $type,
    ]);

    $attachments = [];
    foreach ($data as $attachment) {
        $blobData = $getBlobData($attachment['file_id']);
        if ($blobData) {
            $attachments[] = $blobData;
        }
    }

    return $attachments;
};

//--------------------
// Form mapper
//--------------------

$mapFormFields = function ($id, $type, array &$object) use ($db, $formatter, $tablePrefix) {
    // custom and contact data
    $data = $db->findAll(<<<SQL
            SELECT f.id, v.value, f.type, f.name, f.label
            FROM {$tablePrefix}form_entry_values v
            JOIN {$tablePrefix}form_entry e ON v.entry_id = e.id
            JOIN {$tablePrefix}form_field f ON v.field_id = f.id
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
                    if ($websiteUrl = $formatter->getFormattedUrl($value)) {
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
                if ($phone = $formatter->getFormattedNumber($value)) {
                    $object['contact_data']['phone'][] = [
                        'number' => $phone,
                        'type'   => 'phone',
                    ];
                }
                break;
            case 'datetime':
                $object['custom_fields'][] = [
                    'oid'   => $field['id'],
                    'value' => $formatter->getFormattedDate($value),
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
};

//--------------------
// Custom definitions
//--------------------

$widgetTypeMapping = [
    'text'     => 'text',
    'memo'     => 'textarea',
    'datetime' => 'datetime',
    'choices'  => 'choice',
    'bool'     => 'toggle',
];

$getFormFields = function ($type) use ($db, $tablePrefix, $widgetTypeMapping) {
    $data = $db->findAll(<<<SQL
            SELECT ff.*
            FROM {$tablePrefix}form_field ff
            JOIN {$tablePrefix}form f ON ff.form_id = f.id
            WHERE f.type = :type
SQL
        , [
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
        if (isset($widgetTypeMapping[$n['type']])) {
            $widgetType = $widgetTypeMapping[$n['type']];
        }

        $customDef = [
            'title'       => $n['label'],
            'widget_type' => $widgetType,
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
};

$output->startSection('Organization custom definitions');
foreach ($getFormFields('O') as $id => $formField) {
    $writer->writeOrganizationCustomDef($id, $formField);
}

$output->startSection('User custom definitions');
foreach ($getFormFields('U') as $id => $formField) {
    $writer->writePersonCustomDef($id, $formField);
}

$output->startSection('Ticket custom definitions');
foreach ($getFormFields('T') as $id => $formField) {
    $writer->writeTicketCustomDef($id, $formField);
}

//--------------------
// Organizations
//--------------------

$output->startSection('Organizations');
$pager = $db->getPager("SELECT * FROM {$tablePrefix}organization");

foreach ($pager as $n) {
    $organization = [
        'name'         => $n['name'],
        'date_created' => $formatter->getFormattedDate($n['created']),
    ];

    // custom and contact data
    $mapFormFields($n['id'], 'O', $organization);

    $writer->writeOrganization($n['id'], $organization);
}


//--------------------
// Staff
//--------------------

$output->startSection('Staff');
$pager = $db->getPager("SELECT * FROM {$tablePrefix}staff");

$agentGroupNames = [];
foreach ($db->findAll("SELECT * FROM {$tablePrefix}groups") as $group) {
    $agentGroupNames[$group['group_id']] = $group['group_name'];
}

foreach ($pager as $n) {
    $agent = [
        'name'         => $n['firstname'].' '.$n['lastname'],
        'emails'       => [$n['email']],
        'is_admin'     => $n['isadmin'],
        'is_disabled'  => !$n['isactive'],
        'date_created' => $formatter->getFormattedDate($n['created']),
    ];

    // agent group
    if ($n['group_id'] && isset($agentGroupNames[$n['group_id']])) {
        $agent['agent_groups'][] = $agentGroupNames[$n['group_id']];
    }

    // phone numbers
    if ($phone = $formatter->getFormattedNumber($n['phone'])) {
        $agent['contact_data']['phone'][] = [
            'number' => $phone,
            'type'   => 'phone',
        ];
    }
    if ($mobile = $formatter->getFormattedNumber($n['mobile'])) {
        $agent['contact_data']['phone'][] = [
            'number' => $mobile,
            'type'   => 'mobile',
        ];
    }

    $writer->writeAgent($n['staff_id'], $agent);
}

//--------------------
// Users
//--------------------

$output->startSection('Users');
$pager = $db->getPager("SELECT * FROM {$tablePrefix}user");

foreach ($pager as $n) {
    $user = [
        'name'         => $n['name'],
        'organization' => $n['org_id'],
        'date_created' => $formatter->getFormattedDate($n['created']),
    ];

    // emails
    $userEmails = $db->findAll("SELECT * FROM {$tablePrefix}user_email WHERE user_id = :user_id", [
        'user_id' => $n['id']
    ]);

    foreach ($userEmails as $email) {
        $user['emails'][] = $email['address'];
    }

    // custom and contact data
    $mapFormFields($n['id'], 'U', $user);

    $writer->writeUser($n['id'], $user);
}

//--------------------
// Tickets
//--------------------

$output->startSection('Tickets');
$pager = $db->getPager("SELECT * FROM {$tablePrefix}ticket");

// ticket statuses
$ticketStatusesMap = [
    'open'     => 'awaiting_agent',
    'closed'   => 'resolved',
    'archived' => 'archived',
    'deleted'  => 'hidden.deleted',
];

$ticketStatusesIdMap = [];
foreach ($db->findAll("SELECT * FROM {$tablePrefix}ticket_status") as $ticketStatus) {
    if (isset($ticketStatusesMap[$ticketStatus['state']])) {
        $ticketStatusesIdMap[$ticketStatus['id']] = $ticketStatusesMap[$ticketStatus['state']];
    } else {
        $ticketStatusesIdMap[$ticketStatus['id']] = 'awaiting_agent';
    }
}

// ticket departments
$ticketDepartmentsMap = [];
foreach ($db->findAll("SELECT * FROM {$tablePrefix}department") as $department) {
    $ticketDepartmentsMap[$department['dept_id']] = $department['dept_name'];
}

foreach ($pager as $n) {
    $ticket = [
        'person' => $writer->userOid($n['user_id']),
        'agent'  => $writer->agentOid($n['staff_id']),
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
    $mapFormFields($n['ticket_id'], 'T', $ticket);

    // messages
    $messagesPager = $db->getPager("SELECT * FROM {$tablePrefix}ticket_thread WHERE ticket_id = :ticket_id", [
        'ticket_id' => $n['ticket_id']
    ]);

    foreach ($messagesPager as $m) {
        $message = [
            'oid'     => $m['id'],
            'person'  => $writer->userOid($m['user_id']),
            'message' => $m['body'],
            'is_note' => $m['thread_type'] === 'N',
        ];

        // message attachments
        $messageAttachments = $db->findAll("SELECT * FROM {$tablePrefix}ticket_attachment WHERE ref_id = :message_id", [
            'message_id' => $m['id'],
        ]);

        foreach ($messageAttachments as $attachment) {
            $blobData = $getBlobData($attachment['file_id']);
            if ($blobData) {
                $message['attachments'][] = $blobData;
            }
        }

        $ticket['messages'][] = $message;
    }

    $writer->writeTicket($n['ticket_id'], $ticket);
}

//--------------------
// Article categories
//--------------------

$output->startSection('Article categories');
$pager = $db->getPager("SELECT * FROM {$tablePrefix}faq_category");

foreach ($pager as $n) {
    $writer->writeArticleCategory($n['category_id'], [
        'title'    => $n['name'],
        'is_agent' => !$n['ispublic'],
    ]);
}

//--------------------
// Articles
//--------------------

$output->startSection('Articles');
$pager = $db->getPager("SELECT * FROM {$tablePrefix}faq");

foreach ($pager as $n) {
    $writer->writeArticle($n['faq_id'], [
        'title'       => $n['question'],
        'content'     => $n['answer'],
        'categories'  => [$n['category_id']],
        'status'      => $n['ispublished'] ? 'published' : 'hidden.unpublished',
        'attachments' => $getAttachments($n['faq_id'], 'F'),
    ]);
}

//--------------------
// Settings
//--------------------

$output->startSection('Settings');
$settingMapping = [
    'helpdesk_url'   => 'core.deskpro_url',
    'helpdesk_title' => 'core.deskpro_name',
];

$pager = $db->getPager("SELECT * FROM {$tablePrefix}config WHERE namespace = 'core' AND `key` IN (:setting_names)", [
    'section'       => 'settings',
    'setting_names' => array_keys($settingMapping),
]);

foreach ($pager as $n) {
    $writer->writeSetting($n['id'], [
        'name'  => $settingMapping[$n['key']],
        'value' => $n['value'],
    ]);
}
