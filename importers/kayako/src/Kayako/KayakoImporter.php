<?php

namespace DeskPRO\ImporterTools\Importers\Kayako;

use DeskPRO\ImporterTools\AbstractImporter;

/**
 * Class KayakoImporter.
 */
class KayakoImporter extends AbstractImporter
{
    /**
     * We can have end-user and agent with same email address so prepare mapping their ids mapping by email
     *
     * @var array
     */
    private $userAgentsMapping = [];

    /**
     * {@inheritdoc}
     */
    public function init(array $config)
    {
        if (!isset($config['dbinfo'])) {
            throw new \RuntimeException('Importer config does not have `dbinfo` credentials');
        }

        $this->db()->setCredentials($config['dbinfo']);
    }

    /**
     * {@inheritdoc}
     */
    public function testConfig()
    {
        // try to make a db request to make sure that provided credentials are correct
        $this->db()->findOne('SELECT COUNT(*) FROM swusers');
    }

    /**
     * {@inheritdoc}
     */
    protected function getImportSteps()
    {
        return [
            'organization',
            'person',
            'ticket',
            'article_category',
            'article',
            'news',
            'setting',
        ];
    }

    //--------------------
    // Organizations
    //--------------------

    /**
     * @return void
     */
    protected function organizationImport() {
        $this->progress()->startOrganizationImport();

        if ($this->db()->tableExists('swuserorganizations')) {
            $pager = $this->db()->getPager('SELECT * FROM swuserorganizations');

            foreach ($pager as $n) {
                $organization = [
                    'name'         => $n['organizationname'],
                    'date_created' => date('c', $n['dateline']),
                ];

                // set organization contact data
                // website
                if ($this->formatter()->getFormattedUrl($n['website'])) {
                    $organization['contact_data']['website'][] = [
                        'url' => $this->formatter()->getFormattedUrl($n['website']),
                    ];
                }

                // phone numbers
                if ($this->formatter()->getFormattedNumber($n['phone'])) {
                    $organization['contact_data']['phone'][] = [
                        'number' => $this->formatter()->getFormattedNumber($n['phone']),
                        'type'   => 'phone',
                    ];
                }
                if ($this->formatter()->getFormattedNumber($n['fax'])) {
                    $organization['contact_data']['phone'][] = [
                        'number' => $this->formatter()->getFormattedNumber($n['fax']),
                        'type'   => 'fax',
                    ];
                }

                // address
                if ($n['address']) {
                    $organization['contact_data']['address'][] = [
                        'address' => $n['address'],
                        'city'    => $n['city'],
                        'zip'     => $n['postalcode'],
                        'state'   => $n['state'],
                        'country' => $n['country'],
                    ];
                }

                $this->writer()->writeOrganization($n['userorganizationid'], $organization);
            }
        }
    }

    //--------------------
    // People
    //--------------------

    /**
     * @return void
     */
    protected function personImport()
    {
        $this->progress()->startPersonImport();

        $staffEmailsMapping = [];
        $staffGroups        = [];
        $staffGroupsMapping = [
            'Administrator' => 'All Permissions',
            'Staff'         => 'All Non-Destructive Permissions',
        ];


        $pager = $this->db()->getPager('SELECT * FROM swstaffgroup');
        foreach ($pager as $n) {
            if (isset($staffGroupsMapping[$n['title']])) {
                $staffGroups[$n['staffgroupid']] = $staffGroupsMapping[$n['title']];
            } else {
                $staffGroups[$n['staffgroupid']] = $n['title'];
            }
        }

        $pager = $this->db()->getPager('SELECT * FROM swstaff');
        foreach ($pager as $n) {
            $staffId = $n['staffid'];
            $email   = $n['email'];
            if (!$this->formatter()->isEmailValid($email)) {
                $email = 'imported.agent.'.$staffId.'@example.com';
            }

            $person = [
                'name'        => $n['fullname'],
                'emails'      => [$email],
                'is_disabled' => isset($n['isenabled'])
                    ? !$n['isenabled']
                    : (isset($n['enabled']) ? !$n['enabled'] : false),
            ];

            if ($n['staffgroupid']) {
                $person['agent_groups'][] = $staffGroups[$n['staffgroupid']];
            }
            if (isset($person['agent_groups']) && in_array('All Permissions', $person['agent_groups'])) {
                $person['is_admin'] = true;
            }

            $this->writer()->writeAgent($staffId, $person);
            $staffEmailsMapping[$email] = $staffId;
        }

        $userGroups        = [];
        $userGroupsMapping = [
            'Guest' => 'Everyone',
        ];

        $pager = $this->db()->getPager('SELECT * FROM swusergroups');
        foreach ($pager as $n) {
            if (isset($userGroupsMapping[$n['title']])) {
                $userGroups[$n['usergroupid']] = $userGroupsMapping[$n['title']];
            } else {
                $userGroups[$n['usergroupid']] = $n['title'];
            }
        }

        $pager = $this->db()->getPager('SELECT * FROM swusers');
        foreach ($pager as $n) {
            $userId = $n['userid'];

            $userEmails = [];
            if ($this->db()->columnExists('swuseremails', 'linktypeid')) {
                $emailsRows = $this->db()
                    ->findAll('SELECT * FROM swuseremails WHERE linktypeid = :user_id', ['user_id' => $userId]);
            } else {
                $emailsRows = $this->db()
                    ->findAll('SELECT * FROM swuseremails WHERE userid = :user_id', ['user_id' => $userId]);
            }

            foreach ($emailsRows as $emailsRow) {
                $email = $emailsRow['email'];
                if ($this->formatter()->isEmailValid($email)) {
                    $userEmails[] = $email;

                    // check for agent with the same email address
                    if (isset($staffEmailsMapping[$email])) {
                        $this->userAgentsMapping[$userId] = $staffEmailsMapping[$email];

                        // ignore writing user as we already have the agent with this email address
                        continue 2;
                    }
                }
            }

            if (empty($userEmails)) {
                $userEmails[] = 'imported.user.'.$userId.'@example.com';
            }

            $person = [
                'name'         => $n['fullname'] ?: $userEmails[0],
                'emails'       => $userEmails,
                'is_disabled'  => isset($n['isenabled']) ? !$n['isenabled'] : (isset($n['enabled']) ? !$n['enabled'] : false),
                'date_created' => date('c', $n['dateline']),
            ];

            if (isset($n['userorganizationid'])) {
                $person['organization'] = $n['userorganizationid'];

                if ($person['organization']) {
                    $person['organization_position'] = $n['userdesignation'];
                }
            }

            if ($n['usergroupid'] && isset($userGroups[$n['usergroupid']])) {
                $person['user_groups'][] = $userGroups[$n['usergroupid']];
            }

            $phone = $this->formatter()->getFormattedNumber($n['phone']);
            if ($phone) {
                $person['contact_data']['phone'][] = [
                    'number' => $phone,
                    'type'   => 'phone',
                ];
            }

            $this->writer()->writeUser($userId, $person);
        }
    }

    //--------------------
    // Tickets
    //--------------------

    /**
     * @return void
     */
    protected function ticketImport()
    {
        $this->progress()->startTicketImport();
        $statusMapping = [
            'Open'          => 'awaiting_agent',
            'In Progress'   => 'awaiting_agent',
            'With Engineer' => 'awaiting_agent',
            'Answered'      => 'awaiting_user',
            'On Hold'       => 'on_hold',
            'Overdue'       => 'awaiting_agent',
            'Resolved'      => 'resolved',
            'Closed'        => 'resolved',
        ];

        // prepare a map of ticket departments
        $ticketDepartmentMapping = [];

        if ($this->db()->columnExists('swdepartments', 'parentdepartmentid')) {
            $getTicketDepartments = function ($parentId, $parentTitle = '') use (&$ticketDepartmentMapping, &$getTicketDepartments) {
                $pager = $this->db()->findAll('SELECT * FROM swdepartments WHERE parentdepartmentid = :parent_id', [
                    'parent_id' => $parentId,
                ]);

                foreach ($pager as $n) {
                    $id    = $n['departmentid'];
                    $title = $parentTitle.$n['title'];

                    $ticketDepartmentMapping[$id] = $title;
                    $getTicketDepartments($id, $title.' > ');
                }
            };

            $getTicketDepartments(0);
        } else {
            $pager = $this->db()->findAll('SELECT * FROM swdepartments');
            foreach ($pager as $n) {
                $id    = $n['departmentid'];
                $title = $n['title'];

                $ticketDepartmentMapping[$id] = $title;
            }
        }

        $ticketStatuses = [];
        $pager = $this->db()->findAll('SELECT * FROM swticketstatus');
        foreach ($pager as $status) {
            $ticketStatuses[$status['ticketstatusid']] = $status['title'];
        }

        // get tickets
        $pager = $this->db()->getPager('SELECT * FROM swtickets');
        foreach ($pager as $n) {
            if (isset($this->userAgentsMapping[$n['userid']])) {
                $person = $this->writer()->agentOid($this->userAgentsMapping[$n['userid']]);
            } else {
                $person = $this->writer()->userOid($n['userid']);
            }

            $ticket = [
                'ref'          => $n['ticketmaskid'],
                'subject'      => $n['subject'] ?: 'No subject',
                'person'       => $person,
                'agent'        => $this->writer()->agentOid($n['staffid']),
                'date_created' => date('c', $n['dateline']),
            ];

            if (isset($ticketDepartmentMapping[$n['departmentid']])) {
                $ticket['department'] = $ticketDepartmentMapping[$n['departmentid']];
            } elseif (isset($n['departmenttitle'])) {
                $ticket['department'] = $n['departmenttitle'];
            } else {
                $ticket['department'] = null;
            }

            if (isset($n['ticketstatustitle']) && isset($statusMapping[$n['ticketstatustitle']])) {
                $ticket['status'] = $statusMapping[$n['ticketstatustitle']];
            } elseif (isset($ticketStatuses[$n['ticketstatusid']]) && isset($statusMapping[$ticketStatuses[$n['ticketstatusid']]])) {
                $ticket['status'] = $statusMapping[$ticketStatuses[$n['ticketstatusid']]];
            } else {
                $ticket['status'] = 'awaiting_agent';
            }

            // dp doesn't have 'on_hold' status but has is_hold flag
            if ($ticket['status'] === 'on_hold') {
                $ticket['status']  = 'awaiting_agent';
                $ticket['is_hold'] = true;
            }

            // get ticket messages
            $messagePager = $this->db()->getPager('SELECT * FROM swticketposts WHERE ticketid = :ticket_id', [
                'ticket_id' => $n['ticketid'],
            ]);

            foreach ($messagePager as $m) {
                if (!$m['contents']) {
                    $m['contents'] = 'empty content';
                }

                // multiline formatting
                $m['contents'] = str_replace("\n", '<br/>', $m['contents']);

                $person = null;
                if ($m['userid']) {
                    if (isset($this->userAgentsMapping[$m['userid']])) {
                        $person = $this->writer()->agentOid($this->userAgentsMapping[$m['userid']]);
                    } else {
                        $person = $this->writer()->userOid($m['userid']);
                    }
                } elseif ($m['staffid']) {
                    $person = $this->writer()->agentOid($m['staffid']);
                } elseif ($m['email']) {
                    $person = $m['email'];
                } else {
                    $person = 'imported.message.' . $m['ticketpostid'] . '@example.com';
                }

                $message = [
                    'oid'          => 'post_' . $m['ticketpostid'],
                    'person'       => $person,
                    'message'      => $m['contents'],
                    'date_created' => date('c', $m['dateline']),
                    'attachments'  => [],
                ];

                // get message attachments
                if ($this->db()->columnExists('swattachments', 'linktypeid')) {
                    $attachments = $this->db()->findAll('SELECT * FROM swattachments WHERE ticketid = :ticket_id AND linktypeid = :message_id', [
                        'ticket_id'  => $n['ticketid'],
                        'message_id' => $m['ticketpostid'],
                    ]);
                } else {
                    $attachments = $this->db()->findAll('SELECT * FROM swattachments WHERE ticketid = :ticket_id AND ticketpostid = :message_id', [
                        'ticket_id'  => $n['ticketid'],
                        'message_id' => $m['ticketpostid'],
                    ]);
                }

                foreach ($attachments as $a) {
                    $attachment = [
                        'oid'          => $a['attachmentid'],
                        'file_name'    => $a['filename'] ?: ('attachment'.$a['attachmentid']),
                        'content_type' => $a['filetype'],
                        'blob_data'    => '',
                    ];

                    $attachmentChunks = $this->db()->findAll('SELECT * FROM swattachmentchunks WHERE attachmentid = :attachment_id', [
                        'attachment_id' => $a['attachmentid'],
                    ]);

                    foreach ($attachmentChunks as $c) {
                        if (isset($c['notbase64']) && $c['notbase64']) {
                            $attachment['blob_data'] .= $c['contents'];
                        } elseif (!isset($c['notbase64'])) {
                            $attachment['blob_data'] .= $c['contents'];
                        }
                    }

                    if (!$attachment['blob_data']) {
                        // skip attachments w/o content
                        continue;
                    } else {
                        $attachment['blob_data'] = base64_encode($attachment['blob_data']);
                    }

                    $message['attachments'][] = $attachment;
                }

                $ticket['messages'][] = $message;
            }

            // get ticket notes
            if ($this->db()->columnExists('swticketnotes', 'linktype')) {
                $notesPager = $this->db()
                    ->getPager('SELECT * FROM swticketnotes WHERE linktype = 1 AND linktypeid = :ticket_id', [
                        'ticket_id' => $n['ticketid'],
                    ]);
            } else {
                $notesPager = $this->db()->getPager('SELECT * FROM swticketnotes WHERE typeid = :ticket_id', [
                    'ticket_id' => $n['ticketid'],
                ]);
            }

            foreach ($notesPager as $m) {
                if (empty($m['staffid']) || empty($m['bystaffid'])) {
                    continue;
                }
                if (!$m['note']) {
                    $m['note'] = 'empty content';
                }

                $note = [
                    'oid'          => 'note_' . $m['ticketnoteid'],
                    'message'      => $m['note'],
                    'is_note'      => true,
                    'date_created' => date('c', $m['dateline']),
                ];

                if (isset($m['staffid'])) {
                    $note['person'] = $this->writer()->agentOid($m['staffid']);
                } elseif (isset($m['bystaffid'])) {
                    $note['person'] = $this->writer()->agentOid($m['bystaffid']);
                }

                $ticket['messages'][] = $note;
            }

            if (!$ticket['person']) {
                // person is a mandatory field
                // if it's not set on the ticket then try to get it from the first message
                if (isset($ticket['messages'][0]['person'])) {
                    $ticket['person'] = $ticket['messages'][0]['person'];
                }

                // otherwise generate a fake one to prevent validation errors
                if (!$ticket['person']) {
                    $ticket['person'] = 'imported.ticket.user.'.$n['ticketid'].'@example.com';
                }
            }

            $this->writer()->writeTicket($n['ticketid'], $ticket);
        }
    }

    //--------------------
    // Article categories
    //--------------------

    /**
     * @return void
     */
    protected function articleCategoryImport()
    {
        $this->progress()->startArticleCategoryImport();

        foreach ($this->getArticleCategories(0) as $category) {
            $this->writer()->writeArticleCategory($category['oid'], $category);
        }
    }

    //--------------------
    // Articles
    //--------------------

    /**
     * @return void
     */
    protected function articleImport()
    {
        $this->progress()->startArticleImport();
        // todo need status mapping
        $statusMapping = [
            1 => 'published',
        ];

        $pager = $this->db()->getPager('SELECT * FROM swkbarticles');
        foreach ($pager as $n) {
            if ($this->db()->columnExists('swkbarticlelinks', 'linktypeid')) {
                $categories = $this->db()->findAll('SELECT * FROM swkbarticlelinks WHERE kbarticleid = :article_id AND linktypeid > 0', [
                    'article_id' => $n['kbarticleid']
                ]);
                $categories = array_map(function($category) {
                    return $category['linktypeid'];
                }, $categories);
            } else {
                $categories = $this->db()->findAll('SELECT * FROM swkbarticlelinks WHERE kbarticleid = :article_id AND kbcategoryid > 0', [
                    'article_id' => $n['kbarticleid']
                ]);
                $categories = array_map(function($category) {
                    return $category['kbcategoryid'];
                }, $categories);
            }

            $article = [
                'title'        => $n['subject'],
                'content'      => '',
                'status'       => isset($statusMapping[$n['articlestatus']]) ? $statusMapping[$n['articlestatus']] : 'published',
                'categories'   => $categories,
                'date_created' => date('c', $n['dateline']),
            ];

            if (isset($n['creatorid'])) {
                $article['person'] = $this->writer()->agentOid($n['creatorid']);
            } elseif (isset($n['staffid'])) {
                $article['person'] = $this->writer()->agentOid($n['staffid']);
            }

            // get article content
            $contentPager = $this->db()->getPager('SELECT * FROM swkbarticledata WHERE kbarticleid = :article_id', [
                'article_id' => $n['kbarticleid'],
            ]);

            foreach ($contentPager as $c) {
                $article['content'] .= $c['contents'];
            }

            if (!$article['content']) {
                $article['content'] = 'no content';
            }

            $this->writer()->writeArticle($n['kbarticleid'], $article);
        }
    }

    //--------------------
    // News
    //--------------------

    /**
     * @return void
     */
    protected function newsImport()
    {
        $this->progress()->startNewsImport();

        $newsCategories = [];
        if ($this->db()->tableExists('swnewscategories')) {
            $pager = $this->db()->getPager('SELECT * FROM swnewscategories');
            foreach ($pager as $n) {
                $newsCategories[$n['newscategoryid']] = $n['categorytitle'];
            }
        }

        if ($this->db()->tableExists('swnewsitems')) {
            // todo need status mapping
            $statusMapping = [
                2 => 'published',
            ];

            $pager = $this->db()->getPager('SELECT * FROM swnewsitems');
            foreach ($pager as $n) {
                $news = [
                    'title'        => $n['subject'],
                    'person'       => $this->writer()->agentOid($n['staffid']),
                    'content'      => '',
                    'status'       => isset($statusMapping[$n['newsstatus']]) ? $statusMapping[$n['newsstatus']] : 'published',
                    'date_created' => date('c', $n['dateline']),
                ];

                // get news content
                $contentPager = $this->db()->getPager('SELECT * FROM swnewsitemdata WHERE newsitemid = :news_id', [
                    'news_id' => $n['newsitemid'],
                ]);

                foreach ($contentPager as $c) {
                    $news['content'] .= $c['contents'];
                }

                if (!$news['content']) {
                    $news['content'] = 'no content';
                }

                // get news category
                $category = $this->db()->findOne('SELECT * FROM swnewscategorylinks WHERE newsitemid = :news_id', [
                    'news_id' => $n['newsitemid'],
                ]);

                if ($category && isset($newsCategories[ $category['newscategoryid'] ])) {
                    $news['category'] = $newsCategories[ $category['newscategoryid'] ];
                }

                $this->writer()->writeNews($n['newsitemid'], $news);
            }
        } elseif ($this->db()->tableExists('swnews')) {
            $pager = $this->db()->getPager('SELECT * FROM swnews');
            foreach ($pager as $n) {
                $news = [
                    'title'        => $n['subject'],
                    'person'       => $this->writer()->agentOid($n['staffid']),
                    'content'      => '',
                    'status'       => $n['newstype'] === 'public' ? 'published' : 'hidden.unpublished',
                    'date_created' => date('c', $n['dateline']),
                ];

                // get news content
                $contentPager = $this->db()->getPager('SELECT * FROM swnewsdata WHERE newsid = :news_id', [
                    'news_id' => $n['newsid'],
                ]);

                foreach ($contentPager as $c) {
                    $news['content'] .= $c['contents'];
                }

                if (!$news['content']) {
                    $news['content'] = 'no content';
                }

                $this->writer()->writeNews($n['newsid'], $news);
            }
        }
    }

    //--------------------
    // Chats
    //--------------------

    /**
     * @return void
     */
    protected function chatImport()
    {
        $this->progress()->startChatImport();

        if ($this->db()->tableExists('swchatobjects')) {
            $pager = $this->db()->getPager('SELECT * FROM swchatobjects');
            foreach ($pager as $n) {
                $chat = [
                    'person'       => $n['userid'] ? $this->writer()->userOid($n['userid']) : $n['useremail'],
                    'agent'        => $this->writer()->agentOid($n['staffid']),
                    'date_created' => date('c', $n['dateline']),
                    'date_ended'   => $n['lastpostactivity'] ? date('c', $n['lastpostactivity']) : date('c', $n['dateline']),
                    'ended_by'     => 'user',
                    'messages'     => [],
                ];

                if (isset($n['subject'])) {
                    $chat['subject'] = $n['subject'];
                }

                $participantNameMapping = [
                    $n['userfullname'] => $chat['person'],
                    $n['staffname']    => $chat['agent'],
                ];

                $chatData = $this->db()->findOne('SELECT * FROM swchatdata WHERE chatobjectid = :chat_id', [
                    'chat_id' => $n['chatobjectid'],
                ]);

                $chatMessages = @unserialize($chatData['contents']);
                if (is_array($chatMessages)) {
                    foreach ($chatMessages as $messageId => $m) {
                        if ($m['actiontype'] !== 'message') {
                            continue;
                        }

                        $chat['messages'][] = [
                            'oid'          => $n['chatobjectid'] . '.' . $messageId,
                            'person'       => isset($participantNameMapping[$m['name']]) ? $participantNameMapping[$m['name']] : null,
                            'content'      => $m['base64'] ? base64_decode($m['message']) : $m['message'],
                            'date_created' => date('c', $n['dateline']),
                        ];
                    }
                }

                // skip empty chats
                // don't save chat if no messages
                if (!$chat['messages']) {
                    continue;
                }

                $this->writer()->writeChat($n['chatobjectid'], $chat);
            }
        }
    }

    //--------------------
    // Settings
    //--------------------

    /**
     * @return void
     */
    protected function settingImport()
    {
        $this->progress()->startSettingImport();
        $settingMapping = [
            'general_producturl'  => 'core.deskpro_url',
            'general_companyname' => 'core.deskpro_name',
        ];

        $pager = $this->db()
            ->getPager('SELECT * FROM swsettings WHERE section = :section AND vkey IN (:setting_names)', [
                'section'       => 'settings',
                'setting_names' => array_keys($settingMapping),
            ]);

        foreach ($pager as $n) {
            $this->writer()->writeSetting($n['settingid'], [
                'name'  => $settingMapping[$n['vkey']],
                'value' => $n['data'],
            ]);
        }
    }

    //--------------------
    // Helpers
    //--------------------

    /**
     * @param int $parentId
     *
     * @return array
     */
    protected function getArticleCategories($parentId)
    {
        if ($this->db()->columnExists('swkbcategories', 'parentkbcategoryid')) {
            $pager = $this->db()->getPager('SELECT * FROM swkbcategories WHERE parentkbcategoryid = :parent_id', [
                'parent_id' => $parentId,
            ]);
        } else {
            $pager = $this->db()->getPager('SELECT * FROM swkbcategories WHERE parentcategoryid = :parent_id', [
                'parent_id' => $parentId,
            ]);
        }

        $categories = [];
        foreach ($pager as $n) {
            $categories[] = [
                'oid'        => $n['kbcategoryid'],
                'title'      => $n['title'],
                'categories' => $this->getArticleCategories($n['kbcategoryid']),
            ];
        }

        return $categories;
    }
}
