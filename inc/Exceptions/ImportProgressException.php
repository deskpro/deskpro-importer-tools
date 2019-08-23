<?php

namespace DeskPRO\ImporterTools\Exceptions;

use Exception;
use Throwable;

/**
 * Class ImportProgressException
 * @package DeskPRO\ImporterTools
 */
class ImportProgressException extends Exception
{
    /**
     * @var string
     */
    protected $failedStep;

    /**
     * ImportProgressException constructor.
     *
     * @param $failedStep
     * @param Throwable|null $previous
     */
    public function __construct($failedStep, Throwable $previous = null)
    {
        $this->failedStep = $failedStep;

        parent::__construct("Import process failed on {$this->failedStep}", 0, $previous);
    }

    /**
     * @return string
     */
    public function getFailedStep()
    {
        return $this->failedStep;
    }
}
