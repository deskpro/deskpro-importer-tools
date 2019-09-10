<?php

namespace DeskPRO\ImporterTools;

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
     */
    public function next();
}
