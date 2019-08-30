<?php

namespace DeskPRO\ImporterTools;

use DeskPRO\ImporterTools\Exceptions\PagerException;
use Generator;

/**
 * Interface PagerInterface
 * @package DeskPRO\ImporterTools
 */
interface PagerInterface
{
    /**
     * @param PagerInterface $pager
     *
     * @return Generator
     */
    public static function getIterator(PagerInterface $pager);

    /**
     * @return array
     *
     * @throws PagerException
     */
    public function next();
}
