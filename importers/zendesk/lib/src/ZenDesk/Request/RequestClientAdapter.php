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

namespace DeskPRO\ImporterTools\Importers\ZenDesk\Request;

use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Zendesk\API\Exceptions\ResponseException;
use Zendesk\API\HttpClient;

/**
 * ZenDesk API request adapter via ZenDesk client vendor.
 *
 * Class RequestClientAdapter
 */
class RequestClientAdapter implements RequestAdapterInterface
{
    const CODE_UNAUTHORIZED          = 401;
    const CODE_UN_PROCESSABLE_ENTITY = 422;
    const CODE_TOO_MANY_REQUESTS     = 429;
    const CODE_NOT_FOUND             = 404;

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $wasRequest = false;

    /**
     * Constructor.
     *
     * @param HttpClient      $client
     * @param LoggerInterface $logger
     */
    public function __construct(HttpClient $client, LoggerInterface $logger)
    {
        $this->client  = $client;
        $this->logger  = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function doRequest(Request $request)
    {
        return $this->doApiRequest($request);
    }

    /**
     * Do API request.
     *
     * @param Request $request
     * @param int     $retry_attempt
     *
     * @throws RetryAfterException
     * @throws ResponseException
     *
     * @return \stdClass
     */
    private function doApiRequest(Request $request, $retry_attempt = 0)
    {
        try {
            $endpointClass = $request->getEndpoint();
            if (!class_exists($endpointClass)) {
                trigger_error(sprintf('ZenDesk reader helper class `%s` not found', $endpointClass), E_ERROR);
            }

            $helper = new $endpointClass($this->client);

            $response          = $helper->{$request->getMethod()}($request->getParams());
            $this->wasRequest = true;

            return $response;
        } catch (ResponseException $e) {
            if ($this->client->getDebug()) {
                $debug = $this->client->getDebug();
                $this->logger->error($debug->__toString());

                switch ($debug->lastResponseCode) {
                    case self::CODE_UNAUTHORIZED:
                        throw new RuntimeException(
                            'Unable to connect, check ZenDesk exporter credentials',
                            $e->getCode(), $e
                        );

                    case self::CODE_TOO_MANY_REQUESTS:
                        $timeout = RetryAfterException::parseRetryAfterTimeout($debug->lastResponseHeaders);
                        $this->logger->info("Hit request limit, sleeping for $timeout seconds");

                        return $this->retry(
                            $request,
                            $retry_attempt,
                            new RetryAfterException($e->getMessage(), $timeout),
                            $timeout
                        );

                    case self::CODE_NOT_FOUND:
                    case self::CODE_UN_PROCESSABLE_ENTITY:
                        // nothing to do

                        break;

                    default:
                        $this->logger->error('Unknown API ResponseException. Will retry.');
                        $this->logger->error($e);

                        return $this->retry($request, $retry_attempt, $e);
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Unknown API request error. Will retry.');
            $this->logger->error($e);

            return $this->retry($request, $retry_attempt, $e);
        }

        $this->wasRequest = true;

        return;
    }

    /**
     * Retry api request on error response.
     *
     * @param Request   $request
     * @param int       $retry_attempt
     * @param Exception $exception
     * @param int       $timeout
     *
     * @throws Exception
     * @throws RetryAfterException
     *
     * @return \stdClass
     */
    private function retry(Request $request, $retry_attempt, Exception $exception, $timeout = 0)
    {
        // Retry attempt timeouts (in seconds)
        $retry_timeouts = [2, 5, 10, 30];

        if ($this->wasRequest && $retry_attempt++ < 10) {
            if ($timeout < 1) {
                $timeout = isset($retry_timeouts[$retry_attempt]) ? $retry_timeouts[$retry_attempt] : 60;
            }

            $this->logger->error(sprintf(
                'Retry api request, attempt = %d, timeout = %d.',
                $retry_attempt, $timeout
            ));

            sleep($timeout);

            return $this->doApiRequest($request, $retry_attempt);
        }

        throw $exception;
    }
}
