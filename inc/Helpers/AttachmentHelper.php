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

use DeskPRO\Component\Util\IpUtils;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

/**
 * Class AttachmentHelper.
 */
class AttachmentHelper
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->client = new Client();
        $this->logger = $logger;
    }

    /**
     * @param string $url
     *
     * @return string|null
     */
    public function loadAttachment($url)
    {
        $t = microtime(true);
        if ($this->logger) {
            $this->logger->info("Loading attachment: $url");
        }

        if (!IpUtils::isUrlUserCallable($url)) {
            if ($this->logger) {
                $this->logger->info("URL is not user callable: $url");
            }

            return;
        }

        try {
            $request = new Request('GET', $url);
            $options = [
                'connect_timeout' => 5,
                'timeout'         => 5,
            ];

            $content = base64_encode($this->client->send($request, $options)->getBody()->getContents());
            if ($this->logger) {
                $this->logger->info('Attachment is loaded successfully, took='. (microtime(true) - $t).'s');
            }

            return $content;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Unable to load attachment: $url, took=".(microtime(true) - $t).'s');
                $this->logger->error($e->getMessage());
            }

            return;
        }
    }

    /**
     * @param string $content
     *
     * @return string[]
     */
    public function parseInlineImages($content)
    {
        $urls = [];

        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($content);

        $xpath  = new \DOMXPath($document);
        $images = $xpath->query('//img');

        for ($i = 0; $i < $images->length; $i++) {
            $urls[] = $images->item($i)->getAttribute('src');
        }

        unset($document);

        return $urls;
    }

    /**
     * @param $url
     *
     * @return string|void
     */
    public function getImageContentType($url)
    {
        if (preg_match('#.(png|gif|jpg|jpeg|bmp)($|@)#', $url, $m)) {
            return 'image/'.$m[1];
        }

        return 'image/png';
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function getImageFilenameFromUrl($url)
    {
        if (preg_match('/.*\/([^?]+)/', $url, $matches)) {
            if (isset($matches[1])) {
                return $matches[1];
            }
        }

        return $url;
    }

    /**
     * @param string $content
     * @param string $url
     * @param int    $oid
     * @param string $filename
     *
     * @return string
     */
    public function replaceInlineImage($content, $url, $oid, $filename)
    {
        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($content);

        $xpath  = new \DOMXPath($document);
        $images = $xpath->query('//img');

        for ($i = 0; $i < $images->length; $i++) {
            $item = $images->item($i);
            if ($url === $item->getAttribute('src')) {
                $placeholder = $document->createTextNode("[attach:$oid:$filename]");
                $item->parentNode->replaceChild($placeholder, $item);
            }
        }

        $newContent = $document->saveHTML();
        unset($document);

        return $newContent;
    }
}
