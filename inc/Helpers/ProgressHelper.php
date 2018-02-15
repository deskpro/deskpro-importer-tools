<?php

/*
 * DeskPRO (r) has been developed by DeskPRO Ltd. https://www.deskpro.com/
 * a British company located in London, England.
 *
 * All source code and content Copyright (c) 2016, DeskPRO Ltd.
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

namespace DeskPRO\ImporterTools\Helpers;

use DeskPRO\Bundle\ImportBundle\Event\ProgressEvent;
use DeskPRO\Bundle\ImportBundle\Model;
use DeskPRO\Bundle\ImportBundle\Writer\EntityHandler\EntityHandlerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ProgressHelper.
 */
class ProgressHelper
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $currentModelClass;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityHandlerRegistry    $entityHandlerRegistry
     * @param LoggerInterface          $logger
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityHandlerRegistry    $entityHandlerRegistry,
        LoggerInterface          $logger
    ) {
        $this->eventDispatcher       = $eventDispatcher;
        $this->entityHandlerRegistry = $entityHandlerRegistry;
        $this->logger                = $logger;
    }

    public function finishImport()
    {
        if ($this->currentModelClass) {
            $this->eventDispatcher->dispatch(ProgressEvent::POST_IMPORT, new ProgressEvent($this->currentModelClass));
            $this->currentModelClass = null;
        }
    }

    public function startPersonImport()
    {
        $this->startImport(Model\Person::class);
    }

    public function startPersonCustomDefImport()
    {
        $this->startImport(Model\PersonCustomDef::class);
    }

    public function startOrganizationImport()
    {
        $this->startImport(Model\Organization::class);
    }

    public function startOrganizationCustomDefImport()
    {
        $this->startImport(Model\OrganizationCustomDef::class);
    }

    public function startTicketImport()
    {
        $this->startImport(Model\Ticket::class);
    }

    public function startTicketCustomDefImport()
    {
        $this->startImport(Model\TicketCustomDef::class);
    }

    public function startArticleImport()
    {
        $this->startImport(Model\Article::class);
    }

    public function startArticleCategoryImport()
    {
        $this->startImport(Model\ArticleCategory::class);
    }

    public function startArticleCustomDefImport()
    {
        $this->startImport(Model\ArticleCustomDef::class);
    }

    public function startNewsImport()
    {
        $this->startImport(Model\News::class);
    }

    public function startChatImport()
    {
        $this->startImport(Model\Chat::class);
    }

    public function startChatCustomDefImport()
    {
        $this->startImport(Model\ChatCustomDef::class);
    }

    public function startSettingImport()
    {
        $this->startImport(Model\Setting::class);
    }

    /**
     * @param string $modelClass
     */
    protected function startImport($modelClass)
    {
        $this->finishImport();

        $this->eventDispatcher->dispatch(ProgressEvent::PRE_IMPORT, new ProgressEvent($modelClass));
        $this->currentModelClass = $modelClass;
    }
}
