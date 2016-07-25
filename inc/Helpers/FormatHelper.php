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

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class FormatHelper.
 */
class FormatHelper
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @return FormatHelper
     */
    public static function getHelper()
    {
        global $DP_CONTAINER;

        static $helper;
        if (null === $helper) {
            $helper = new self($DP_CONTAINER->get('validator'), $DP_CONTAINER->get('dp.importer_logger'));
        }

        return $helper;
    }

    /**
     * Constructor.
     *
     * @param ValidatorInterface $validator
     * @param LoggerInterface    $logger
     */
    public function __construct(ValidatorInterface $validator, LoggerInterface $logger)
    {
        $this->validator = $validator;
        $this->logger    = $logger;
    }

    /**
     * @param string $number
     *
     * @return string
     */
    public function getFormattedNumber($number)
    {
        if (!$number) {
            return '';
        }

        $numberUtil   = PhoneNumberUtil::getInstance();
        $parsedNumber = null;

        $countryCodes = ['', '+'];
        foreach (range(1, 10) as $codeNum) {
            $countryCodes[] = '+'.$codeNum;
        }

        foreach ($countryCodes as $countryCode) {
            try {
                $checkNumber  = $countryCode.' '.$number;
                $parsedNumber = $numberUtil->parse($checkNumber, null);

                if ($numberUtil->isValidNumber($parsedNumber)) {
                    break;
                }
            } catch (\Exception $e) {
            }
        }

        if (!$parsedNumber) {
            $this->logger->warning("Unable to parse phone number `$number`.");

            return '';
        }
        if (!$numberUtil->isValidNumber($parsedNumber)) {
            $this->logger->warning("Phone number `$number` is not valid.");

            return '';
        }

        return $numberUtil->format($parsedNumber, PhoneNumberFormat::E164);
    }

    /**
     * @param string $url
     *
     * @return bool|string
     */
    public function getFormattedUrl($url)
    {
        if (!$url) {
            return '';
        }

        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            $url = 'http://'.$url;
        }

        $errors = $this->validator->validate($url, [
            new Assert\Url(),
        ]);

        if (count($errors)) {
            $this->logger->warning("Url `$url` is not valid");

            return '';
        }

        return $url;
    }
}
