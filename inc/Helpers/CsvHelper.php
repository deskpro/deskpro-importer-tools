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
 * Class CsvHelper.
 */
class CsvHelper
{
    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @param string $filename
     * @param string $delimeter
     * @param string $enclosure
     *
     * @throws \Exception
     *
     * @return array
     */
    public function readFile($filename, $delimeter, $enclosure)
    {
        $data   = [];
        $header = null;

        $fp = @fopen($filename, 'r');
        if (!$fp) {
            throw new \Exception("Unable to read file $filename");
        }

        while (($row = fgetcsv($fp, null, $delimeter, $enclosure)) !== false) {
            if (!$header) {
                $header = [];
                foreach ($row as $key => $value) {
                    $value = trim($value);
                    $value = preg_replace('/[^\w\d\s_-]/', '', $value);

                    $header[$key] = $value;
                }
            } else {
                $item = [];
                foreach ($row as $key => $value) {
                    if (isset($header[$key])) {
                        $item[$header[$key]] = $value;
                    }
                }

                $data[] = $item;
            }
        }

        fclose($fp);

        return $data;
    }
}
