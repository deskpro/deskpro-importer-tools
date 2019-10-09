<?php

namespace DeskPRO\ImporterTools\Importers\Zendesk;

use DeskPRO\ImporterTools\AbstractImporter;

/**
 * Class ZendeskImporter.
 */
class ZendeskImporter extends AbstractImporter
{
    /**
     * @var ZendeskReader
     */
    private $reader;

    /**
     * @var \DateTime
     */
    private $startTime;

    /**
     * @var string
     */
    private $ticketBrandField;

    /**
     * @var array
     */
    private $ticketCustomDefMap = [];

    /**
     * {@inheritdoc}
     */
    public function init(array $config)
    {
        if (!isset($config['start_time'])) {
            // default time offset
            $config['start_time'] = '-2 years';
        }

        $this->reader = new ZendeskReader($this->logger, $this->container->get('dp.importer.event_dispatcher'));
        $this->reader->setConfig($config['account']);
        $this->writer()->setOidPrefix($config['account']['subdomain']);

        $this->startTime        = new \DateTime($config['start_time']);
        $this->ticketBrandField = !empty($config['ticket_brand_field']) ? $config['ticket_brand_field'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function testConfig()
    {
        // try to make an api request to make sure that provided credentials are correct
        $this->reader->getOrganizationFields();
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
            'users',
            'tickets',
            'article_category',
            'articles',
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

        foreach ($this->reader->getOrganizationFields() as $n) {
            $this->writer()->writeOrganizationCustomDef($n['id'], $this->mapCustomDef($n));
        }
    }

    /**
     * @return void
     */
    protected function personCustomDefImport()
    {
        $this->progress()->startPersonCustomDefImport();

        foreach ($this->reader->getPersonFields() as $n) {
            $this->writer()->writePersonCustomDef($n['id'], $this->mapCustomDef($n));
        }
    }

    /**
     * @return void
     */
    protected function ticketCustomDefImport()
    {
        $this->progress()->startTicketCustomDefImport();

        $this->ticketCustomDefMap = [];

        foreach ($this->reader->getTicketFields() as $n) {
            $customDef                          = $this->mapCustomDef($n);
            $this->ticketCustomDefMap[$n['id']] = $customDef['title'];

            $this->writer()->writeTicketCustomDef($n['id'], $customDef);
        }
    }

    /**
     * @return void
     */
    protected function organizationImport() {
        $this->progress()->startOrganizationImport();
        foreach ($this->reader->getOrganizations() as $n) {
            $organization = [
                'name'         => $n['name'],
                'date_created' => $n['created_at'],
                'labels'       => $n['tags'],
            ];

            // custom fields
            foreach ($n['organization_fields'] as $c) {
                if (!isset($c['value']) || !$c['value']) {
                    continue;
                }

                $organization['custom_fields'][] = [
                    'oid'   => $c['id'],
                    'value' => $c['value'],
                ];
            }

            $this->writer()->writeOrganization($n['id'], $organization);
        }
    }

    /**
     * @param \DateTime $offset
     *
     * @return void
     * @throws \Exception
     */
    protected function usersImport($offset)
    {
        $this->progress()->startPersonImport();
        $pager = $this->reader->getPersonPager($offset);

        foreach ($pager as $n) {
            $this->writePerson($n);
        }
    }

    /**
     * @param \DateTime $offset
     *
     * @return void
     * @throws \Exception
     */
    protected function ticketsImport($offset)
    {
        $this->progress()->startTicketImport();
        $statusMapping = [
            'new'     => 'awaiting_agent',
            'open'    => 'awaiting_agent',
            'pending' => 'awaiting_user',
            'hold'    => 'awaiting_agent',
            'solved'  => 'resolved',
            'closed'  => 'resolved',
            'deleted' => 'hidden.deleted',
        ];

        if (empty($this->ticketCustomDefMap)) {
            foreach ($this->reader->getTicketFields() as $n) {
                $customDef = $this->mapCustomDef($n);
                $this->ticketCustomDefMap[$n['id']] = $customDef['title'];
            }
        }

        $pager = $this->reader->getTicketPager($offset);
        foreach ($pager as $n) {
            $ticket = [
                'subject'      => $n['subject'] ?: 'No Subject',
                'status'       => $statusMapping[$n['status']],
                'person'       => $n['requester_id'],
                'agent'        => $n['assignee_id'],
                'labels'       => $n['tags'],
                'organization' => $n['organization_id'],
                'date_created' => $n['created_at'],
                'participants' => $n['collaborator_ids'],
            ];

            // custom fields
            foreach ($n['custom_fields'] as $c) {
                if (!$c['value']) {
                    continue;
                }

                if (!empty($CONFIG['ticket_brand_field'])
                    && isset($this->ticketCustomDefMap[$c['id']])
                    && $this->ticketCustomDefMap[$c['id']] === $CONFIG['ticket_brand_field']) {
                    $ticket['brand'] = $c['value'];
                } else {
                    $ticket['custom_fields'][] = [
                        'oid'   => $c['id'],
                        'value' => $c['value'],
                    ];
                }
            }

            // messages
            foreach ($this->reader->getTicketComments($n['id']) as $c) {
                $message = [
                    'oid'          => $c['id'],
                    'person'       => $c['author_id'],
                    'message'      => $c['html_body'],
                    'is_note'      => !$c['public'],
                    'date_created' => $c['created_at'],
                ];

                foreach ($c['attachments'] as $a) {
                    $blobData = $this->attachments()->loadAttachment($a['content_url']);
                    if (!$blobData) {
                        continue;
                    }

                    $message['attachments'][] = [
                        'oid'          => $a['id'],
                        'file_name'    => $a['file_name'],
                        'content_type' => $a['content_type'],
                        'blob_data'    => $blobData,
                    ];
                }

                $ticket['messages'][] = $message;
            }

            $this->writer()->writeTicket($n['id'], $ticket);
        }
    }

    /**
     * @return void
     */
    protected function articleCategoryImport()
    {
        $this->progress()->startArticleCategoryImport();
        $sections = [];
        foreach ($this->reader->getArticlesSections() as $n) {
            $accessPolicy = !empty($n['access_policy']) ? $n['access_policy'] : ['viewable_by' => 'staff'];

            $sections[$n['category_id']][] = [
                'oid'         => $n['id'],
                'title'       => $n['name'],
                'user_groups' => $accessPolicy['viewable_by'] === 'everybody' ? ['everyone'] : ['registered'],
                'is_agent'    => $accessPolicy['viewable_by'] === 'staff',
            ];
        }

        foreach ($this->reader->getArticlesCategories() as $n) {
            $category = [
                'title'      => $n['name'],
                'categories' => isset($sections[$n['id']]) ? $sections[$n['id']] : [],
            ];

            $this->writer()->writeArticleCategory($n['id'], $category);
        }
    }

    /**
     * @param \DateTime $offset
     *
     * @return void
     * @throws \Exception
     */
    protected function articlesImport($offset)
    {
        $this->progress()->startArticleImport();
        $pager = $this->reader->getArticlePager($offset);

        foreach ($pager as $n) {
            $article = [
                'person'       => $n['author_id'],
                'title'        => $n['title'],
                'content'      => $n['body'],
                'categories'   => [$n['section_id']],
                'labels'       => $n['label_names'],
                'date_created' => $n['created_at'],
                'date_updated' => $n['updated_at'],
                'language'     => ZendeskMapper::getLanguageByLocale([$n['locale']]),
                'status'       => $n['draft'] ? 'hidden.draft' : 'published',
            ];

            // comments
            foreach ($this->reader->getArticleComments($n['id']) as $c) {
                $article['comments'][] = [
                    'oid'          => $c['id'],
                    'content'      => $c['body'],
                    'person'       => $c['author_id'],
                    'date_created' => $c['created_at'],
                    'status'       => 'visible',
                ];
            }

            // attachments
            foreach ($this->reader->getArticleAttachments($n['id']) as $a) {
                $blobData = $this->attachments()->loadAttachment($a['content_url']);
                if (!$blobData) {
                    continue;
                }

                $article['attachments'][] = [
                    'oid'          => $a['id'],
                    'file_name'    => $a['file_name'],
                    'content_type' => $a['content_type'],
                    'blob_data'    => $blobData,
                ];
            }

            // translations
            foreach ($this->reader->getArticleTranslations($n['id']) as $t) {
                $language = ZendeskMapper::getLanguageByLocale([$t['locale']]);
                if (!$language) {
                    continue;
                }

                $article['title_translations'][] = [
                    'language' => $language,
                    'value'    => $t['title'],
                ];

                $article['content_translations'][] = [
                    'language' => $language,
                    'value'    => $t['title'],
                ];
            }

            if ($n['draft']) {
                $article['status'] = 'hidden.draft';
            } else {
                $article['status'] = 'published';
            }

            $this->writer()->writeArticle($n['id'], $article);
        }
    }

    //--------------------
    // Helpers
    //--------------------

    /**
     * @param array $data
     *
     * @return array
     */
    protected function mapCustomDef(array $data)
    {
        $customDef = [
            'title'           => isset($data['title_in_portal']) && $data['title_in_portal']
                ? $data['title_in_portal']
                : $data['title'],
            'description'     => $data['description'],
            'is_enabled'      => $data['active'],
            'is_user_enabled' => $data['active'],
            'is_agent_field'  => isset($data['visible_in_portal']) && !$data['visible_in_portal'],
            'widget_type'     => ZendeskMapper::$customFieldWidgetTypeMapping[$data['type']],
        ];

        foreach (['system_field_options', 'custom_field_options'] as $optionGroup) {
            if (!empty($data[$optionGroup])) {
                foreach ($data[$optionGroup] as $option) {
                    $customDef['choices'][] = [
                        'title' => $option['value'],
                    ];
                }
            }
        }

        return $customDef;
    }

    /**
     * @param array $user
     *
     * @return void
     */
    protected function writePerson($user)
    {
        $validTimezones = \DateTimeZone::listIdentifiers();
        $validTimezones = array_combine($validTimezones, $validTimezones);

        // user could have empty email, add auto generated one
        if (!$user['email']) {
            $user['email'] = 'imported.user.'.$user['id'].'@example.com';
        }

        $person = [
            'name'         => $user['name'],
            'emails'       => [$user['email']],
            'date_created' => $user['created_at'],
            'organization' => $user['organization_id'],
        ];

        if (isset(ZendeskMapper::$timezoneMapping[$user['time_zone']])) {
            $person['timezone'] = ZendeskMapper::$timezoneMapping[$user['time_zone']];
        } elseif (isset($validTimezones[$user['time_zone']])) {
            $person['timezone'] = $validTimezones[$user['time_zone']];
        } else {
            $person['timezone'] = 'UTC';
            $this->output()->warning("Found unknown timezone `{$user['time_zone']}`");
        }

        // custom fields
        foreach ($user['user_fields'] as $c) {
            if (!isset($c['value']) || !$c['value']) {
                continue;
            }

            $person['custom_fields'][] = [
                'oid'   => $c['id'],
                'value' => $c['value'],
            ];
        }

        if ($user['role'] === 'admin') {
            $person['is_admin'] = true;
            $this->writer()->writeAgent($user['id'], $person, false);
        } elseif ($user['role'] === 'agent') {
            $this->writer()->writeAgent($user['id'], $person, false);
        } else {
            $this->writer()->writeUser($user['id'], $person, false);
        }
    }

    /**
     * @param string $step
     * @param array  $offsets
     * @param mixed  $default
     *
     * @return \DateTime
     * @throws \Exception
     */
    protected function getStepOffset($step, $offsets, $default = null)
    {
        $offset = parent::getStepOffset($step, $offsets, $default);

        return $offset !== null
            ? new \DateTime("@{$offset}")
            : $this->startTime;
    }
}
