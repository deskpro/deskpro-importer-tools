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
use DeskPRO\ImporterTools\AbstractPager;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DbPager.
 */
class DbPager extends AbstractPager
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $query;

    /**
     * @var string
     */
    private $step;

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var array
     */
    private $types = [];

    /**
     * @var int
     */
    private $pageNum;

    /**
     * @var int
     */
    private $perPage;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Constructor.
     *
     * @param Connection               $connection
     * @param EventDispatcherInterface $eventDispatcher
     * @param string                   $step
     * @param string                   $query
     * @param array                    $params
     * @param int                      $pageNum
     * @param int                      $perPage
     */
    public function __construct(
        Connection $connection,
        EventDispatcherInterface $eventDispatcher,
        $step,
        $query,
        array $params = [],
        $pageNum = 1,
        $perPage = 1000
    ) {
        if ($perPage < 1) {
            throw new \RuntimeException('Per page number should be greater than 1.');
        }

        $this->connection      = $connection;
        $this->eventDispatcher = $eventDispatcher;
        $this->query           = $query;
        $this->step            = $step;
        $this->pageNum         = $pageNum;
        $this->perPage         = $perPage;
        $this->params          = $params;
        $this->types           = DbHelper::getParamTypes($params);
    }

    /**
     * @return array
     */
    public function next()
    {
        $limit     = ($this->pageNum - 1) * $this->perPage;
        $statement = $this->connection->executeQuery(
            "{$this->query} LIMIT $limit, {$this->perPage}",
            $this->params,
            $this->types
        );

        $this->eventDispatcher->dispatch(
            ProgressEvent::POST_STEP_IMPORT,
            new ProgressEvent(null, ['offset' => [$this->step => $this->pageNum]])
        );

        ++$this->pageNum;

        return $statement->fetchAll();
    }
}
