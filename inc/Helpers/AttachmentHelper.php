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

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

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
     * Constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param string $url
     *
     * @return string|null
     */
    public function loadAttachment($url)
    {
        try {
            return base64_encode($this->client->send(new Request('GET', $url))->getBody()->getContents());
        } catch (\Exception $e) {
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

        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($content);

        $xpath  = new \DOMXPath($document);
        $images = $xpath->query('//img');

        for ($i = 0; $i < $images->length; $i++) {
            $urls[] = $images->item($i)->getAttribute('src');
        }

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
     * @param string $content
     * @param string $url
     * @param int    $oid
     *
     * @return string
     */
    public function replaceInlineImage($content, $url, $oid)
    {
        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($content);

        $xpath  = new \DOMXPath($document);
        $images = $xpath->query('//img');

        for ($i = 0; $i < $images->length; $i++) {
            $item = $images->item($i);
            if ($url === $item->getAttribute('src')) {
                $placeholder = $document->createTextNode("[attach:$oid:$url]");
                $item->parentNode->replaceChild($placeholder, $item);
            }
        }

        return $document->saveHTML();
    }
}
