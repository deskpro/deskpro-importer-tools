<?php

namespace DeskPRO\ImporterTools;

use Generator;

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
