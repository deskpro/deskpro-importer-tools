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
     * @var array
     */
    protected static $callingCodes = [
        '1'     => 'United States',
        '44'    => 'United Kingdom',
        '7 840' => 'Abkhazia',
        '7 940' => 'Abkhazia',
        '93'    => 'Afghanistan',
        '355'   => 'Albania',
        '213'   => 'Algeria',
        '1 684' => 'American Samoa',
        '376'   => 'Andorra',
        '244'   => 'Angola',
        '1 264' => 'Anguilla',
        '1 268' => 'Antigua and Barbuda',
        '54'    => 'Argentina',
        '374'   => 'Armenia',
        '297'   => 'Aruba',
        '247'   => 'Ascension',
        '61'    => 'Australia',
        '672'   => 'Australian External Territories',
        '43'    => 'Austria',
        '994'   => 'Azerbaijan',
        '1 242' => 'Bahamas',
        '973'   => 'Bahrain',
        '880'   => 'Bangladesh',
        '1 246' => 'Barbados',
        '375'   => 'Belarus',
        '32'    => 'Belgium',
        '501'   => 'Belize',
        '229'   => 'Benin',
        '1 441' => 'Bermuda',
        '975'   => 'Bhutan',
        '591'   => 'Bolivia',
        '387'   => 'Bosnia and Herzegovina',
        '267'   => 'Botswana',
        '55'    => 'Brazil',
        '246'   => 'British Indian Ocean Territory',
        '1 284' => 'British Virgin Islands',
        '673'   => 'Brunei',
        '359'   => 'Bulgaria',
        '226'   => 'Burkina Faso',
        '257'   => 'Burundi',
        '855'   => 'Cambodia',
        '237'   => 'Cameroon',
        '238'   => 'Cape Verde',
        '345'   => 'Cayman Islands',
        '236'   => 'Central African Republic',
        '235'   => 'Chad',
        '56'    => 'Chile',
        '86'    => 'China',
        '57'    => 'Colombia',
        '269'   => 'Comoros',
        '242'   => 'Congo',
        '243'   => 'Congo, Dem. Rep. of (Zaire)',
        '682'   => 'Cook Islands',
        '506'   => 'Costa Rica',
        '225'   => 'Ivory Coast',
        '385'   => 'Croatia',
        '53'    => 'Cuba',
        '599'   => 'Curacao',
        '537'   => 'Cyprus',
        '420'   => 'Czech Republic',
        '45'    => 'Denmark',
        '253'   => 'Djibouti',
        '1 767' => 'Dominica',
        '1 809' => 'Dominican Republic',
        '1 829' => 'Dominican Republic',
        '1 849' => 'Dominican Republic',
        '670'   => 'East Timor',
        '593'   => 'Ecuador',
        '20'    => 'Egypt',
        '503'   => 'El Salvador',
        '240'   => 'Equatorial Guinea',
        '291'   => 'Eritrea',
        '372'   => 'Estonia',
        '251'   => 'Ethiopia',
        '500'   => 'Falkland Islands',
        '298'   => 'Faroe Islands',
        '679'   => 'Fiji',
        '358'   => 'Finland',
        '33'    => 'France',
        '596'   => 'French Antilles',
        '594'   => 'French Guiana',
        '689'   => 'French Polynesia',
        '241'   => 'Gabon',
        '220'   => 'Gambia',
        '995'   => 'Georgia',
        '49'    => 'Germany',
        '233'   => 'Ghana',
        '350'   => 'Gibraltar',
        '30'    => 'Greece',
        '299'   => 'Greenland',
        '1 473' => 'Grenada',
        '590'   => 'Guadeloupe',
        '1 671' => 'Guam',
        '502'   => 'Guatemala',
        '224'   => 'Guinea',
        '245'   => 'Guinea-Bissau',
        '595'   => 'Guyana',
        '509'   => 'Haiti',
        '504'   => 'Honduras',
        '852'   => 'Hong Kong SAR China',
        '36'    => 'Hungary',
        '354'   => 'Iceland',
        '91'    => 'India',
        '62'    => 'Indonesia',
        '98'    => 'Iran',
        '964'   => 'Iraq',
        '353'   => 'Ireland',
        '972'   => 'Israel',
        '39'    => 'Italy',
        '1 876' => 'Jamaica',
        '81'    => 'Japan',
        '962'   => 'Jordan',
        '7 7'   => 'Kazakhstan',
        '254'   => 'Kenya',
        '686'   => 'Kiribati',
        '850'   => 'North Korea',
        '82'    => 'South Korea',
        '965'   => 'Kuwait',
        '996'   => 'Kyrgyzstan',
        '856'   => 'Laos',
        '371'   => 'Latvia',
        '961'   => 'Lebanon',
        '266'   => 'Lesotho',
        '231'   => 'Liberia',
        '218'   => 'Libya',
        '423'   => 'Liechtenstein',
        '370'   => 'Lithuania',
        '352'   => 'Luxembourg',
        '853'   => 'Macau SAR China',
        '389'   => 'Macedonia',
        '261'   => 'Madagascar',
        '265'   => 'Malawi',
        '60'    => 'Malaysia',
        '960'   => 'Maldives',
        '223'   => 'Mali',
        '356'   => 'Malta',
        '692'   => 'Marshall Islands',
        '222'   => 'Mauritania',
        '230'   => 'Mauritius',
        '262'   => 'Mayotte',
        '52'    => 'Mexico',
        '691'   => 'Micronesia',
        '1 808' => 'Midway Island',
        '373'   => 'Moldova',
        '377'   => 'Monaco',
        '976'   => 'Mongolia',
        '382'   => 'Montenegro',
        '1664'  => 'Montserrat',
        '212'   => 'Morocco',
        '95'    => 'Myanmar',
        '264'   => 'Namibia',
        '674'   => 'Nauru',
        '977'   => 'Nepal',
        '31'    => 'Netherlands',
        '1 869' => 'Nevis',
        '687'   => 'New Caledonia',
        '64'    => 'New Zealand',
        '505'   => 'Nicaragua',
        '227'   => 'Niger',
        '234'   => 'Nigeria',
        '683'   => 'Niue',
        '1 670' => 'Northern Mariana Islands',
        '47'    => 'Norway',
        '968'   => 'Oman',
        '92'    => 'Pakistan',
        '680'   => 'Palau',
        '970'   => 'Palestine',
        '507'   => 'Panama',
        '675'   => 'Papua New Guinea',
        '51'    => 'Peru',
        '63'    => 'Philippines',
        '48'    => 'Poland',
        '351'   => 'Portugal',
        '1 787' => 'Puerto Rico',
        '1 939' => 'Puerto Rico',
        '974'   => 'Qatar',
        '40'    => 'Romania',
        '7'     => 'Russia',
        '250'   => 'Rwanda',
        '685'   => 'Samoa',
        '378'   => 'San Marino',
        '966'   => 'Saudi Arabia',
        '221'   => 'Senegal',
        '381'   => 'Serbia',
        '248'   => 'Seychelles',
        '232'   => 'Sierra Leone',
        '65'    => 'Singapore',
        '421'   => 'Slovakia',
        '386'   => 'Slovenia',
        '677'   => 'Solomon Islands',
        '27'    => 'South Africa',
        '34'    => 'Spain',
        '94'    => 'Sri Lanka',
        '249'   => 'Sudan',
        '597'   => 'Suriname',
        '268'   => 'Swaziland',
        '46'    => 'Sweden',
        '41'    => 'Switzerland',
        '963'   => 'Syria',
        '886'   => 'Taiwan',
        '992'   => 'Tajikistan',
        '255'   => 'Tanzania',
        '66'    => 'Thailand',
        '228'   => 'Togo',
        '690'   => 'Tokelau',
        '676'   => 'Tonga',
        '1 868' => 'Trinidad and Tobago',
        '216'   => 'Tunisia',
        '90'    => 'Turkey',
        '993'   => 'Turkmenistan',
        '1 649' => 'Turks and Caicos Islands',
        '688'   => 'Tuvalu',
        '256'   => 'Uganda',
        '380'   => 'Ukraine',
        '971'   => 'United Arab Emirates',
        '598'   => 'Uruguay',
        '1 340' => 'U.S. Virgin Islands',
        '998'   => 'Uzbekistan',
        '678'   => 'Vanuatu',
        '58'    => 'Venezuela',
        '84'    => 'Vietnam',
        '681'   => 'Wallis and Futuna',
        '967'   => 'Yemen',
        '260'   => 'Zambia',
        '263'   => 'Zimbabwe',
    ];

    /**
     * @return FormatHelper
     */
    public static function getHelper()
    {
        /** @var mixed $DP_CONTAINER */
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

        $number = trim($number);
        $number = preg_replace('#[^0-9]#', '', $number);
        $number = preg_replace('#^0+#', '', $number);

        if (!$number) {
            return '';
        }

        $numberUtil   = PhoneNumberUtil::getInstance();
        $parsedNumber = null;

        $countryCodes = ['', '+'];
        foreach (array_keys(self::$callingCodes) as $codeNum) {
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

        $url = trim($url);
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            $url = 'http://'.$url;
        }

        $errors = $this->validator->validate($url, [
            new Assert\Url(),
        ]);

        if (count($errors)) {
            $this->logger->warning("Url `$url` is not valid.");

            return '';
        }

        return $url;
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function isEmailValid($email)
    {
        if (!$email) {
            return false;
        }

        $errors = $this->validator->validate($email, [
            new Assert\Email([
                'strict' => true,
            ]),
        ]);

        if (count($errors)) {
            $this->logger->warning("Email `$email` is not valid.");

            return false;
        }

        return true;
    }

    /**
     * @param string $date
     *
     * @return string
     */
    public function getFormattedDate($date)
    {
        try {
            if (is_int($date) || ctype_digit($date)) {
                $date = '@'.$date;
            }

            $date = new \DateTime($date);
        } catch (\Exception $e) {
            $date = new \DateTime();
        }

        return $date->format('c');
    }
}
