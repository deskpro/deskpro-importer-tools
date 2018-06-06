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
use DeskPRO\Bundle\ImportBundle\Storage\Storage;
use DeskPRO\Bundle\ImportBundle\Writer\EntityHandler\EntityHandlerRegistry;
use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ScriptHelper.
 */
class WriteHelper
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityHandlerRegistry
     */
    private $entityHandlerRegistry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $initialPages = [];

    /**
     * @var array
     */
    private $writtenCounts = [];

    /**
     * @var Model\PrimaryImportModelInterface
     */
    private $lastModel;

    /**
     * @var array
     */
    private $batchMapping;

    /**
     * @var string
     */
    private $oidPrefix;

    /**
     * Constructor.
     *
     * @param Serializer               $serializer
     * @param Storage                  $storage
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityHandlerRegistry    $entityHandlerRegistry
     * @param LoggerInterface          $logger
     */
    public function __construct(
        Serializer               $serializer,
        Storage                  $storage,
        EventDispatcherInterface $eventDispatcher,
        EntityHandlerRegistry    $entityHandlerRegistry,
        LoggerInterface          $logger
    ) {
        $this->serializer            = $serializer;
        $this->storage               = $storage;
        $this->eventDispatcher       = $eventDispatcher;
        $this->entityHandlerRegistry = $entityHandlerRegistry;
        $this->logger                = $logger;
    }

    /**
     * @param string $oidPrefix
     *
     * @return $this
     */
    public function setOidPrefix($oidPrefix)
    {
        $this->oidPrefix = $oidPrefix;

        return $this;
    }

    /**
     * @param string $oid
     *
     * @return string
     */
    public function userOid($oid)
    {
        return $oid ? 'user_'.$oid : null;
    }

    /**
     * @param string $oid
     *
     * @return string
     */
    public function agentOid($oid)
    {
        return $oid ? 'agent_'.$oid : null;
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeArticle($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Article::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeArticleCategory($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\ArticleCategory::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeArticleCustomDef($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\ArticleCustomDef::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeDownload($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Download::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeFeedback($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Feedback::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeFeedbackCustomDef($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\FeedbackCustomDef::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeNews($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\News::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeOrganization($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Organization::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeOrganizationCustomDef($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\OrganizationCustomDef::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     * @param bool       $oidWithPrefix
     */
    public function writeUser($oid, array $data, $oidWithPrefix = true)
    {
        if ($oidWithPrefix) {
            $oid = $this->userOid($oid);
        }

        $this->writePerson($oid, $data);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     * @param bool       $oidWithPrefix
     */
    public function writeAgent($oid, array $data, $oidWithPrefix = true)
    {
        if ($oidWithPrefix) {
            $oid = $this->agentOid($oid);
        }

        $this->writePerson($oid, array_merge($data, [
            'is_agent' => true,
        ]));
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writePersonCustomDef($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\PersonCustomDef::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeTicket($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Ticket::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeTicketCustomDef($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\TicketCustomDef::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeTextSnippet($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\TextSnippet::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeTextSnippetCategory($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\TextSnippetCategory::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeChat($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Chat::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeChatCustomDef($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\ChatCustomDef::class);
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    public function writeSetting($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Setting::class);
    }

    /**
     * @param int|string $oid
     *
     * @return array
     */
    public function getTicket($oid)
    {
        return $this->getModelData($oid, Model\Ticket::class);
    }

    /**
     * @param int|string $oid
     * @param bool       $oidWithPrefix
     *
     * @return array
     */
    public function getUser($oid, $oidWithPrefix = true)
    {
        if ($oidWithPrefix) {
            $oid = $this->userOid($oid);
        }

        return $this->getModelData($oid, Model\Person::class);
    }

    /**
     * @param int|string $oid
     * @param bool       $oidWithPrefix
     *
     * @return array
     */
    public function getAgent($oid, $oidWithPrefix = true)
    {
        if ($oidWithPrefix) {
            $oid = $this->agentOid($oid);
        }

        return $this->getModelData($oid, Model\Person::class);
    }

    /**
     * @param int|string $oid
     *
     * @return array
     */
    public function getTicketCustomDef($oid)
    {
        return $this->getModelData($oid, Model\TicketCustomDef::class);
    }

    public function printLastModel()
    {
        $this->logger->debug($this->serializer->serialize($this->lastModel, 'json'));
    }

    /**
     * @param int|string $oid
     * @param array      $data
     */
    protected function writePerson($oid, array $data)
    {
        $this->writeModel($oid, $data, Model\Person::class);
    }

    /**
     * @param int    $oid
     * @param array  $rawData
     * @param string $modelClass
     *
     * @throws \Exception
     */
    protected function writeModel($oid, array $rawData, $modelClass)
    {
        $this->lastModel = null;
        $modelType       = $this->entityHandlerRegistry->getTypeByModelClass($modelClass);

        $this->logger->debug("Export $modelType #$oid");

        try {
            $this->eventDispatcher->dispatch(ProgressEvent::PRE_MODEL_IMPORT, new ProgressEvent($modelClass));

            // transform to model
            $model = $this->serializer->fromArray($rawData, $modelClass);
            $model->setOid($oid);
            $model->setRawData($rawData);
            if ($this->oidPrefix) {
                $model->setOidPrefix($this->oidPrefix);
            }

            $this->lastModel = $model;
            $this->storage->writeModel($model, $this->getBatchId($model));

            // if it's new item then update batch cursor position and batch mapping, otherwise just skip it
            if (!isset($this->batchMapping[$modelClass][$oid])) {
                $this->batchMapping[$modelClass][$oid] = $this->getBatchId($model);

                // remember num count to calc destination path properly
                if (!isset($this->writtenCounts[$modelClass])) {
                    $this->writtenCounts[$modelClass] = 0;
                }

                $this->writtenCounts[$modelClass]++;
            }

            $this->eventDispatcher->dispatch(ProgressEvent::POST_MODEL_IMPORT, new ProgressEvent($modelClass));
        } catch (\Exception $e) {
            $this->logger->error("Unable to write model: {$e->getMessage()}");
            $this->logger->error('Raw data:');
            $this->logger->error(json_encode($rawData));
        }
    }

    /**
     * @param int    $oid
     * @param string $modelClass
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function getModelData($oid, $modelClass)
    {
        try {
            if (!isset($this->batchMapping[$modelClass][$oid])) {
                return;
            }

            $batchId = $this->batchMapping[$modelClass][$oid];

            return json_decode($this->storage->readModel($modelClass, $batchId, $oid), true);
        } catch (\Exception $e) {
            $this->logger->error("Unable to restore $modelClass #$oid model: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * @param Model\PrimaryImportModelInterface $model
     *
     * @return int
     */
    protected function getBatchId(Model\PrimaryImportModelInterface $model)
    {
        $modelClass = get_class($model);
        if (isset($this->batchMapping[$modelClass][$model->getOid()])) {
            return $this->batchMapping[$modelClass][$model->getOid()];
        }
        if (!isset($this->initialPages[$modelClass])) {
            $this->initialPages[$modelClass] = $this->storage->getNextBatchId($modelClass);
        }

        if (!isset($this->writtenCounts[$modelClass])) {
            $this->writtenCounts[$modelClass] = 0;
        }

        return $this->initialPages[$modelClass] + (int) ($this->writtenCounts[$modelClass] / 100);
    }
}
