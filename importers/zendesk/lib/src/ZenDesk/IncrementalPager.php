<?php

/*
 * DeskPRO (r) has been developed by DeskPRO Ltd. https://www.deskpro.com/
 * a British company located in London, England.
 *
 * All source code and content Copyright (c) 2015, DeskPRO Ltd.
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

namespace DeskPRO\ImporterTools\Importers\ZenDesk;

use DeskPRO\ImporterTools\Importers\ZenDesk\Request\Request;
use DeskPRO\ImporterTools\Importers\ZenDesk\Request\RequestAdapterInterface;

/**
 * Class IncrementalPager.
 */
class IncrementalPager
{
    /**
     * @var RequestAdapterInterface
     */
    private $adapter;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var string
     */
    private $property;

    /**
     * @var \DateTime
     */
    private $startTime;

    /**
     * @var bool
     */
    private $wasRequest = false;

    /**
     * @var null
     */
    private $lastHash = null;

    /**
     * Constructor.
     *
     * @param RequestAdapterInterface $adapter
     * @param Request                 $request
     * @param string                  $property
     * @param \DateTime               $startTime
     */
    public function __construct(RequestAdapterInterface $adapter, Request $request, $property, \DateTime $startTime)
    {
        $this->adapter   = $adapter;
        $this->request   = $request;
        $this->property  = $property;
        $this->startTime = $startTime;
    }

    /**
     * @return array
     */
    public function getNext()
    {
        $this->request->setParams([
            'start_time' => $this->startTime->getTimestamp(),
        ]);

        $items  = [];
        $result = $this->adapter->doRequest($this->request);
        if ($result) {
            if (is_array($result->{$this->property})) {
                foreach ($result->{$this->property} as $item) {
                    $items[] = ZenDeskReader::toArray($item);
                }
            }

            // if there was request and request data less than 2 then we suppose that it's end of fetching
            // end return empty result
            if ($result->count < 2 && $this->wasRequest) {
                $items = [];
            }

            // update start time for next request
            $this->startTime->setTimestamp($result->end_time);
        }

        // prevent infinity loops
        $hash = md5(serialize($items));
        if ($hash === $this->lastHash) {
            $items = [];
        }

        $this->wasRequest = true;
        $this->lastHash   = $hash;

        return $items;
    }
}
