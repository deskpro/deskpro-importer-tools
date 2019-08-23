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

namespace DeskPRO\ImporterTools;

use Application\DeskPRO\Entity\Job;
use DeskPRO\Component\Util\StringUtils;
use DeskPRO\ImporterTools\Exceptions\ImportProgressException;
use DeskPRO\ImporterTools\Helpers\AttachmentHelper;
use DeskPRO\ImporterTools\Helpers\CsvHelper;
use DeskPRO\ImporterTools\Helpers\DbHelper;
use DeskPRO\ImporterTools\Helpers\FormatHelper;
use DeskPRO\ImporterTools\Helpers\OutputHelper;
use DeskPRO\ImporterTools\Helpers\ProgressHelper;
use DeskPRO\ImporterTools\Helpers\WriteHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Exception\MethodNotImplementedException;

/**
 * Class AbstractImporter.
 */
abstract class AbstractImporter implements ImporterInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $helpers;

    /**
     * @var Job
     */
    protected $job;

    /**
     * Constructor.
     *
     * @param LoggerInterface    $logger
     * @param ContainerInterface $container
     */
    public function __construct(LoggerInterface $logger, ContainerInterface $container)
    {
        $this->logger    = $logger;
        $this->container = $container;
    }

    /**
     * @return void
     *
     * @throws ImportProgressException
     */
    public function runImport() {
        if ($this->job instanceof Job) {
            $importedSteps = $this->job->getDataKey('imported_steps');
        }

        foreach ($this->getImportSteps() as $step) {
            $method = StringUtils::toCamelCase($step).'Import';
            try {
                if (!method_exists($this, $method)) {
                    throw new MethodNotImplementedException($step);
                }

                if (isset($importedSteps) && in_array($step, $importedSteps, true)) {
                    continue;
                }

                $this->$method();
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                throw new ImportProgressException($step, $exception);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addHelper($helper)
    {
        $this->helpers[get_class($helper)] = $helper;

        return $this;
    }

    /**
     * @param Job $job
     */
    public function setJob(Job $job)
    {
        $this->job = $job;
    }

    /**
     * @return array
     */
    abstract protected function getImportSteps();

    /**
     * @param string $class
     *
     * @return mixed
     */
    protected function getHelper($class)
    {
        if (!isset($this->helpers[$class])) {
            throw new \RuntimeException("Helper $class not found");
        }

        return $this->helpers[$class];
    }

    /**
     * @return ProgressHelper
     */
    protected function progress()
    {
        return $this->getHelper(ProgressHelper::class);
    }

    /**
     * @return WriteHelper
     */
    protected function writer()
    {
        return $this->getHelper(WriteHelper::class);
    }

    /**
     * @return OutputHelper
     */
    protected function output()
    {
        return $this->getHelper(OutputHelper::class);
    }

    /**
     * @return FormatHelper
     */
    protected function formatter()
    {
        return $this->getHelper(FormatHelper::class);
    }

    /**
     * @return AttachmentHelper
     */
    protected function attachments()
    {
        return $this->getHelper(AttachmentHelper::class);
    }

    /**
     * @return DbHelper
     */
    protected function db()
    {
        return $this->getHelper(DbHelper::class);
    }

    /**
     * @return CsvHelper
     */
    protected function csv()
    {
        return $this->getHelper(CsvHelper::class);
    }
}
