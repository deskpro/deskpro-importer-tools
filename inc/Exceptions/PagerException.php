<?php

namespace DeskPRO\ImporterTools\Exceptions;

use Throwable;
use Exception;

/**
 * Class PagerException
 * @package DeskPRO\ImporterTools\Exceptions
 */
class PagerException extends Exception
{
    /**
     * Could be page number or timestamp(for Zendesk incremental import)
     *
     * @var int
     */
    protected $failedPage;

    /**
     * @var string
     */
    protected $failedStepName;

    /**
     * PagerException constructor.
     *
     * @param $failedPage
     * @param Throwable|null $previous
     */
    public function __construct($failedPage, Throwable $previous = null)
    {
        $this->failedPage = $failedPage;

        parent::__construct("Error during fetch data on page {$this->failedPage}" , 0, $previous);
    }

    /**
     * @return int
     */
    public function getFailedPage()
    {
        return $this->failedPage;
    }

    /**
     * @return string|null
     */
    public function getFailedStepName()
    {
        return $this->failedStepName;
    }

    /**
     * @param string|null $stepName
     *
     * @return void
     */
    public function setFailedStepName($stepName = null)
    {
        $this->failedStepName = $stepName;
    }
}
