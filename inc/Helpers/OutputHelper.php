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

use Psr\Log\LoggerInterface;

/**
 * Class OutputHelper.
 */
class OutputHelper
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $currentSection;

    /**
     * @return OutputHelper
     */
    public static function getHelper()
    {
        global $DP_CONTAINER;

        static $helper;
        if (null === $helper) {
            $helper = new self($DP_CONTAINER->get('dp.importer_logger'));
        }

        return $helper;
    }

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function startSection($title)
    {
        $this->finishSection();
        $this->currentSection = $title;

        $this->logger->info('<comment>');
        $this->logger->info(sprintf('Export `%s` section.', $title));
        $this->logger->info('=====================================');
        $this->logger->info('</comment>');
    }

    public function finishSection()
    {
        if (!$this->currentSection) {
            return;
        }

        $this->logger->info('<comment>');
        $this->logger->info('=====================================');
        $this->logger->info(sprintf('Completed `%s` section.', $this->currentSection));
        $this->logger->info('</comment>');

        $this->currentSection = null;
    }

    public function finishProcess()
    {
        $this->finishSection();
        $this->logger->info('<comment>All done.</comment>');
    }

    /**
     * @param string $message
     */
    public function debug($message)
    {
        $this->logger->debug($message);
    }

    /**
     * @param string $message
     */
    public function info($message)
    {
        $this->logger->info($message);
    }

    /**
     * @param string $message
     */
    public function warning($message)
    {
        $this->logger->warning($message);
    }

    /**
     * @param string $message
     */
    public function error($message)
    {
        $this->logger->error($message);
    }

    /**
     * @param string $message
     */
    public function notice($message)
    {
        $this->logger->notice($message);
    }

    /**
     * @param string $message
     */
    public function alert($message)
    {
        $this->logger->alert($message);
    }
}
