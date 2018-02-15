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

namespace DeskPRO\ImporterTools\Importers\Zendesk\Request;

use DateTime;

/**
 * If the rate limit is exceeded, Zendesk responds with an HTTP 429 Too Many Requests response code.
 *
 * Class RetryAfterException
 *
 * @see https://developer.zendesk.com/rest_api/docs/help_center/introduction
 * @see https://support.zendesk.com/hc/en-us/articles/203691336-Best-practices-for-avoiding-rate-limiting
 */
final class RetryAfterException extends \RuntimeException
{
    /**
     * @var DateTime
     */
    private $request_date;

    /**
     * @var int
     */
    private $timeout;

    /**
     * Constructor.
     *
     * @param string $message
     * @param int    $timeout
     */
    public function __construct($message, $timeout)
    {
        parent::__construct($message);

        $this->request_date = new DateTime();
        $this->timeout      = (int) $timeout;
    }

    /**
     * Request date.
     *
     * @return DateTime
     */
    public function getRequestDate()
    {
        return $this->request_date;
    }

    /**
     * Request time with retry after timeout.
     *
     * @return DateTime
     */
    public function getRetryAfterTime()
    {
        $date_time = clone $this->request_date;

        return $date_time->modify('+'.$this->timeout);
    }

    /**
     * Retry after timeout in seconds.
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Get retry timeout from response headers.
     *
     * @param string $raw_headers
     *
     * @throws \Exception
     *
     * @return int
     */
    public static function parseRetryAfterTimeout($raw_headers)
    {
        $headers = http_parse_headers($raw_headers);
        if (is_array($headers)) {
            if (isset($headers['Retry-After'])) {
                return (int) $headers['Retry-After'];
            }
        }

        // default sleep timeout
        return 10;
    }
}

/*
 * Hook if http.so is not installed
 */
if (!function_exists('http_parse_headers')) {
    function http_parse_headers($raw_headers)
    {
        $headers = [];
        foreach (explode("\n", $raw_headers) as $i => $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                $headers[$h[0]] = trim($h[1]);
            }
        }

        return $headers;
    }
}
