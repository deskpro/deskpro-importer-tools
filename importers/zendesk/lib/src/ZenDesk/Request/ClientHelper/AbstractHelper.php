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

namespace DeskPRO\ImporterTools\Importers\ZenDesk\Request\ClientHelper;

use Zendesk\API\Http;
use Zendesk\API\HttpClient;
use Zendesk\API\Exceptions\MissingParametersException;
use Zendesk\API\Exceptions\ResponseException;

/**
 * Base ZenDesk request client helper.
 *
 * Class AbstractHelper
 */
abstract class AbstractHelper
{
    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * Constructor.
     *
     * @param HttpClient $client
     */
    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Sends a get request.
     * Some of end points are not implemented in ZenDesk api client library.
     *
     * @param string $endpoint
     *
     * @throws ResponseException
     *
     * @return mixed
     */
    protected function doGetRequest($endpoint)
    {
        $response = Http::send($this->client, $endpoint);

        if (!is_object($response) || $this->client->getDebug()->lastResponseCode != 200) {
            throw new ResponseException(__METHOD__);
        }

        $this->client->setSideload(null);

        return $response;
    }

    /**
     * Incremental exports with a supplied start_time
     * Not implemented in zendesk_api_client_php yet.
     *
     * @param string $type
     * @param array  $options
     * @param string $apiGroup
     *
     * @throws MissingParametersException
     * @throws ResponseException
     *
     * @return \stdClass
     *
     * @see https://developer.zendesk.com/rest_api/docs/core/incremental_export
     * @see https://support.zendesk.com/hc/en-us/articles/204396193-New-Incremental-APIs-now-available-to-all-accounts?preview%5Btheme_id%5D=202201216&use_theme_settings=false
     */
    protected function doIncrementalExportRequest($type, array $options, $apiGroup = '')
    {
        if (!$options['start_time']) {
            throw new MissingParametersException(__METHOD__, ['start_time']);
        }

        $endpoint = rtrim($apiGroup, '/').'/'.sprintf('incremental/%s.json?start_time=%s', $type, $options['start_time']);

        return $this->doGetRequest($endpoint);
    }

    /**
     * Sends a post request.
     *
     * @param string $endPoint
     * @param array  $options
     *
     * @throws ResponseException
     *
     * @return mixed
     */
    protected function doPostRequest($endPoint, array $options)
    {
        $response = Http::send($this->client, $endPoint, array_merge($options, [
            'method' => 'POST',
        ]));

        if (!is_object($response) || !in_array($this->client->getDebug()->lastResponseCode, [200, 204])) {
            throw new ResponseException($endPoint);
        }

        $this->client->setSideload(null);

        return $response;
    }

    /**
     * Sends a delete request.
     *
     * @param string $endPoint
     *
     * @throws ResponseException
     *
     * @return mixed
     */
    protected function doDeleteRequest($endPoint)
    {
        $response = Http::send($this->client, $endPoint, ['method' => 'DELETE']);
        if (!in_array($this->client->getDebug()->lastResponseCode, [200, 204])) {
            throw new ResponseException($endPoint);
        }

        $this->client->setSideload(null);

        return $response;
    }
}
