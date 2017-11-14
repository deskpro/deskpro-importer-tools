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

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Zendesk\API\Exceptions\ApiResponseException;
use Zendesk\API\HttpClient;

/**
 * Zendesk API request adapter via Zendesk client vendor.
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
     * @param int     $retryAttempt
     *
     * @throws RetryAfterException
     * @throws \Exception
     *
     * @return \stdClass
     */
    private function doApiRequest(Request $request, $retryAttempt = 0)
    {
        try {
            $endpointClass = $request->getEndpoint();
            if (!class_exists($endpointClass)) {
                trigger_error(sprintf('Zendesk reader helper class `%s` not found', $endpointClass), E_ERROR);
            }

            $helper = new $endpointClass($this->client);

            $response         = $helper->{$request->getMethod()}($request->getParams());
            $this->wasRequest = true;

            return $response;
        } catch (ApiResponseException $e) {
            if ($this->client->getDebug()) {
                $debug = $this->client->getDebug();

                /** @var BadResponseException $error */
                $error = $debug->lastResponseError;
                if ($error instanceof BadResponseException) {
                    $errorCode = $error->getCode();
                } else {
                    if ($debug->lastResponseError instanceof RequestException) {
                        return $this->retry($request, $retryAttempt, $e);
                    } elseif ($debug->lastResponseError instanceof \Exception) {
                        throw $debug->lastResponseError;
                    } else {
                        throw $e;
                    }
                }

                switch ($errorCode) {
                    case self::CODE_UNAUTHORIZED:
                        throw new RuntimeException(
                            'Unable to connect, check Zendesk credentials',
                            $e->getCode(), $e
                        );

                    case self::CODE_TOO_MANY_REQUESTS:
                        $timeout = RetryAfterException::parseRetryAfterTimeout($debug->lastResponseHeaders);
                        $this->logger->info("Hit request limit, sleeping for $timeout seconds");

                        return $this->retry(
                            $request,
                            $retryAttempt,
                            new RetryAfterException($e->getMessage(), $timeout),
                            $timeout
                        );

                    case self::CODE_NOT_FOUND:
                    case self::CODE_UN_PROCESSABLE_ENTITY:
                        if (strpos($e->getErrorDetails(), 'There is no help desk configured at this address.')) {
                            throw new RuntimeException(
                                'Unable to connect, there is no help desk configured at this address.',
                                $e->getCode(), $e
                            );
                        }

                        // else nothing to do

                        break;

                    default:
                        $this->logger->error('Unknown api response exception. Will retry.');
                        $this->logger->error($e);

                        $this->logger->error($debug->lastRequestBody);
                        $this->logger->error($debug->lastResponseCode);
                        $this->logger->error($debug->lastResponseError);

                        return $this->retry($request, $retryAttempt, $e);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Unknown API request error. Will retry.');
            $this->logger->error($e);

            return $this->retry($request, $retryAttempt, $e);
        }

        $this->wasRequest = true;

        return;
    }

    /**
     * Retry api request on error response.
     *
     * @param Request    $request
     * @param int        $retryAttempt
     * @param \Exception $exception
     * @param int        $timeout
     *
     * @throws \Exception
     * @throws RetryAfterException
     *
     * @return \stdClass
     */
    private function retry(Request $request, $retryAttempt, \Exception $exception, $timeout = 0)
    {
        // Retry attempt timeouts (in seconds)
        $retry_timeouts = [2, 5, 10, 30];

        if ($this->wasRequest && $retryAttempt++ < 10) {
            if ($timeout < 1) {
                $timeout = isset($retry_timeouts[$retryAttempt]) ? $retry_timeouts[$retryAttempt] : 60;
            }

            $this->logger->error(sprintf(
                'Retry api request, attempt = %d, timeout = %d.',
                $retryAttempt, $timeout
            ));

            sleep($timeout);

            return $this->doApiRequest($request, $retryAttempt);
        }

        throw $exception;
    }
}
