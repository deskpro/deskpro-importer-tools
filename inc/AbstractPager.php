<?php

namespace DeskPRO\ImporterTools;

use Generator;
use DeskPRO\ImporterTools\Exceptions\PagerException;

/**
 * Class AbstractPager
 * @package DeskPRO\ImporterTools
 */
abstract class AbstractPager implements PagerInterface
{
    /**
     * @param PagerInterface $pager
     *
     * @return Generator
     * @throws PagerException
     */
    public static function getIterator(PagerInterface $pager)
    {
        while ($data = $pager->next()) {
            foreach ($data as $n) {
                yield $n;
            }
        }
    }
}
