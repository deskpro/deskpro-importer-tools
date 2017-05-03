<?php

/*
 * DeskPRO (r) has been developed by DeskPRO Ltd. https://www.deskpro.com/
 * a British company located in London, England.
 *
 * All source code and content Copyright (c) 2015, DeskPRO Ltd.
 *
 * The license agreement under which this software is released
 * can be found at https://www.deskpro.com/eula/
 *
 * By using this software, you acknowledge having read the license
 * and agree to be bound thereby.
 *
 * Please note that DeskPRO is not free software. We release the full
 * source code for our software because we trust our users to pay us for
 * the huge investment in time and energy that has gone into both creating
 * this software and supporting our customers. By providing the source code
 * we preserve our customers' ability to modify, audit and learn from our
 * work. We have been developing DeskPRO since 2001, please help us make it
 * another decade.
 *
 * Like the work you see? Think you could make it better? We are always
 * looking for great developers to join us: http://www.deskpro.com/jobs/
 *
 * ~ Thanks, Everyone at Team DeskPRO
 */

namespace DeskPRO\ImporterTools\Importers\ZenDesk;

use DeskPRO\ImporterTools\Importers\ZenDesk\Request\ClientHelper\CoreAPI;
use DeskPRO\ImporterTools\Importers\ZenDesk\Request\ClientHelper\HelpCenter;
use DeskPRO\ImporterTools\Importers\ZenDesk\Request\Request;
use DeskPRO\ImporterTools\Importers\ZenDesk\Request\RequestAdapterInterface;
use DeskPRO\ImporterTools\Importers\ZenDesk\Request\RequestCacheAdapter;
use DeskPRO\ImporterTools\Importers\ZenDesk\Request\RequestClientAdapter;
use DeskPRO\ImporterTools\Importers\ZenDesk\Request\RetryAfterException;
use Zendesk\API\HttpClient;

/**
 * ZenDesk reader.
 *
 * @see https://developer.zendesk.com/rest_api/docs/core/introduction
 * @see https://developer.zendesk.com/rest_api/docs/core/incremental_export
 * @see https://support.zendesk.com/hc/en-us/articles/204232743
 *
 * Class ZenDeskReader
 */
class ZenDeskReader
{
    const FIELD_TYPE_SYSTEM_SUBJECT        = 'subject';
    const FIELD_TYPE_SYSTEM_DESCRIPTION    = 'description';
    const FIELD_TYPE_SYSTEM_STATUS         = 'status';
    const FIELD_TYPE_SYSTEM_TICKET_TYPE    = 'tickettype';
    const FIELD_TYPE_SYSTEM_PRIORITY       = 'priority';
    const FIELD_TYPE_SYSTEM_BASIC_PRIORITY = 'basic_priority';
    const FIELD_TYPE_SYSTEM_GROUP          = 'group';
    const FIELD_TYPE_SYSTEM_ASSIGNEE       = 'assignee';
    const FIELD_TYPE_TAGGER                = 'tagger';
    const FIELD_TYPE_CHECKBOX              = 'checkbox';
    const FIELD_TYPE_DATE                  = 'date';
    const FIELD_TYPE_DECIMAL               = 'decimal';
    const FIELD_TYPE_DROPDOWN              = 'dropdown';
    const FIELD_TYPE_INTEGER               = 'integer';
    const FIELD_TYPE_REGEXP                = 'regexp';
    const FIELD_TYPE_TEXT                  = 'text';
    const FIELD_TYPE_TEXTAREA              = 'textarea';

    /**
     * @var RequestAdapterInterface
     */
    private $adapter;

    /**
     * {@inheritdoc}
     */
    public static function createReader(array $params)
    {
        $httpClient = new HttpClient(@$params['subdomain']);
        $httpClient->setAuth(@$params['auth_type'], $params);

        /** @var mixed $DP_CONTAINER */
        global $DP_CONTAINER;

        $adapter      = new RequestClientAdapter($httpClient, $DP_CONTAINER->get('dp.importer_logger'));
        $cacheAdapter = new RequestCacheAdapter($adapter);

        return new ZenDeskReader($cacheAdapter);
    }

    /**
     * Constructor.
     *
     * @param RequestAdapterInterface $adapter
     */
    public function __construct(RequestAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Returns a batch of the users collection.
     *
     * @param \DateTime $startTime
     *
     * @throws RetryAfterException
     *
     * @return IncrementalPager
     */
    public function getPersonPager(\DateTime $startTime)
    {
        $request = new Request(CoreAPI\Person::class, 'incrementalExport');

        return IncrementalPager::getIterator(new IncrementalPager($this->adapter, $request, 'users', $startTime));
    }

    /**
     * Returns user fields collection.
     *
     * @throws RetryAfterException
     *
     * @return array
     */
    public function getPersonFields()
    {
        $fields = [];
        $result = $this->adapter->doRequest(new Request(CoreAPI\PersonField::class, 'findAll'));

        if ($result) {
            foreach ($result->user_fields as $field) {
                $fields[] = $this->toArray($field);
            }
        }

        return $fields;
    }

    /**
     * Returns organizations collection.
     *
     * @return array
     */
    public function getOrganizations()
    {
        $result = $this->adapter->doRequest(new Request(CoreAPI\Organization::class, 'findAll'));

        return $result ? $this->toArray($result->organizations) : [];
    }

    /**
     * Returns organization fields collection.
     *
     * @return mixed
     */
    public function getOrganizationFields()
    {
        $fields = [];
        $result = $this->adapter->doRequest(new Request(CoreAPI\OrganizationField::class, 'findAll'));

        if ($result) {
            foreach ($result->organization_fields as $field) {
                $fields[] = $this->toArray($field);
            }
        }

        return $fields;
    }

    /**
     * Returns a batch of the tickets collection.
     *
     * @param \DateTime $startTime
     *
     * @throws RetryAfterException
     *
     * @return IncrementalPager
     */
    public function getTicketPager(\DateTime $startTime = null)
    {
        $request = new Request(CoreAPI\Ticket::class, 'incrementalExport');

        return IncrementalPager::getIterator(new IncrementalPager($this->adapter, $request, 'tickets', $startTime));
    }

    /**
     * Returns a collection of ticket comments.
     *
     * @param int $id
     *
     * @return array
     */
    public function getTicketComments($id)
    {
        $comments = [];
        $result   = $this->adapter->doRequest(new Request(CoreAPI\TicketComment::class, 'findAll', [
            'ticket_id' => $id,
        ]));

        if ($result) {
            foreach ($result->comments as $comment) {
                $comments[] = $this->toArray($comment);
            }
        }

        return $comments;
    }

    /**
     * Returns ticket fields collection.
     * Returns a batch end time of the tickets collection.
     *
     * @throws RetryAfterException
     *
     * @return array
     */
    public function getTicketFields()
    {
        $fields = [];
        $result = $this->adapter->doRequest(new Request(CoreAPI\TicketField::class, 'findAll'));

        $skip_types = [
            self::FIELD_TYPE_SYSTEM_ASSIGNEE,
            self::FIELD_TYPE_SYSTEM_SUBJECT,
            self::FIELD_TYPE_SYSTEM_DESCRIPTION,
            self::FIELD_TYPE_SYSTEM_STATUS,
            self::FIELD_TYPE_SYSTEM_PRIORITY,
            self::FIELD_TYPE_SYSTEM_BASIC_PRIORITY,
            self::FIELD_TYPE_SYSTEM_GROUP,
        ];

        if ($result) {
            foreach ($result->ticket_fields as $field) {
                if (in_array($field->type, $skip_types)) {
                    continue;
                }

                $fields[] = $this->toArray($field);
            }
        }

        return $fields;
    }

    /**
     * Returns article category path like "Category Name > Section Name".
     *
     * @param int $section_id
     *
     * @return string
     */
    public function getArticleCategoryPath($section_id)
    {
        $response_categories = $this->adapter->doRequest(new Request(HelpCenter\Category::class, 'findAll'));
        $response_categories = $this->toArray($response_categories->categories);

        $response_sections = $this->adapter->doRequest(new Request(HelpCenter\Section::class, 'findAll'));
        $response_sections = $this->toArray($response_sections->sections);

        $categories = [];
        foreach ($response_categories as $category) {
            $categories[$category['id']] = $category;
        }

        $sections = [];
        foreach ($response_sections as $category) {
            $sections[$category['id']] = $category;
        }

        if (isset($sections[$section_id])) {
            $section = $sections[$section_id];
        } else {
            $response_section = $this->adapter->doRequest(new Request(HelpCenter\Section::class, 'find', [
                'id' => $section_id,
            ]));

            return $this->toArray($response_section->section);
        }

        if (!empty($section)) {
            if (isset($categories[$section['category_id']])) {
                $category = $categories[$section['category_id']];
            } else {
                $response_section = $this->adapter->doRequest(new Request(HelpCenter\Category::class, 'find', [
                    'id' => $section['category_id'],]
                ));

                $category = $this->toArray($response_section->category);
            }

            if (!empty($category)) {
                return $category['name'].' > '.$section['name'];
            } else {
                return $section['name'];
            }
        }

        return '';
    }

    /**
     * Returns a batch of the articles collection.
     *
     * @param \DateTime|null $startTime
     *
     * @return IncrementalPager
     */
    public function getArticlePager(\DateTime $startTime = null)
    {
        $request = new Request(HelpCenter\Article::class, 'incrementalExport');

        return IncrementalPager::getIterator(new IncrementalPager($this->adapter, $request, 'articles', $startTime));
    }

    /**
     * Returns a collection of article comments.
     *
     * @param int $id
     *
     * @return array
     */
    public function getArticleComments($id)
    {
        $comments = [];
        $result   = $this->adapter->doRequest(new Request(HelpCenter\ArticleComment::class, 'findAll', [
            'id' => $id,
        ]));

        if ($result) {
            foreach ($result->comments as $comment) {
                $comments[] = $this->toArray($comment);
            }
        }

        return $comments;
    }

    /**
     * Returns a collection of article attachments.
     *
     * @param int $id
     *
     * @return array
     */
    public function getArticleAttachments($id)
    {
        $attachments = [];
        $result      = $this->adapter->doRequest(new Request(HelpCenter\ArticleAttachment::class, 'findAll', [
            'id' => $id,
        ]));

        if ($result) {
            foreach ($result->article_attachments as $attachment) {
                $attachments[] = $this->toArray($attachment);
            }
        }

        return $attachments;
    }

    /**
     * Returns a collection of article translations.
     *
     * @param $id
     *
     * @return array
     */
    public function getArticleTranslations($id)
    {
        $translations = [];
        $result       = $this->adapter->doRequest(new Request(HelpCenter\ArticleTranslation::class, 'findAll', [
            'id' => $id,
        ]));

        if ($result) {
            foreach ($result->translations as $translation) {
                $translations[] = $this->toArray($translation);
            }
        }

        return $translations;
    }

    /**
     * Returns a collection of article categories.
     *
     * @return array
     */
    public function getArticlesCategories()
    {
        $categories = [];
        $result     = $this->adapter->doRequest(new Request(HelpCenter\Category::class, 'findAll'));

        if ($result) {
            foreach ($result->categories as $category) {
                $categories[] = $this->toArray($category);
            }
        }

        return $categories;
    }

    /**
     * Returns a collection of article sub categories.
     *
     * @return array
     */
    public function getArticlesSections()
    {
        $sections = [];
        $result   = $this->adapter->doRequest(new Request(HelpCenter\Section::class, 'findAll'));

        if ($result) {
            foreach ($result->sections as $section) {
                $section = $this->toArray($section);
                $access  = $this->adapter->doRequest(new Request(HelpCenter\SectionAccessPolicy::class, 'find', [
                    'id' => $section['id'],]
                ));
                $access  = $access ? $this->toArray($access) : null;
                $section = array_merge($section, $access);

                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * Converts stdClass to array.
     *
     * @param mixed $object
     *
     * @return array
     */
    public static function toArray($object)
    {
        return json_decode(json_encode($object), true);
    }
}
