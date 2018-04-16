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

use Application\DeskPRO\NewSettings\SettingsResolver;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Orb\Data\Countries;
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
     * @var SettingsResolver
     */
    private $settingsResolver;

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

    protected static $countryTimeZones = [
        'AD' => 'Europe/Andorra',
        'AE' => 'Asia/Dubai',
        'AF' => 'Asia/Kabul',
        'AG' => 'America/Antigua',
        'AI' => 'America/Anguilla',
        'AL' => 'Europe/Tirane',
        'AM' => 'Asia/Yerevan',
        'AN' => 'America/Curacao',
        'AO' => 'Africa/Luanda',
        'AQ' => 'Antarctica/Casey',
        'AR' => 'America/Argentina/Buenos_Aires',
        'AS' => 'Pacific/Pago_Pago',
        'AT' => 'Europe/Vienna',
        'AU' => 'Australia/Sydney',
        'AW' => 'America/Aruba',
        'AX' => 'Europe/Mariehamn',
        'AZ' => 'Asia/Baku',
        'BA' => 'Europe/Sarajevo',
        'BB' => 'America/Barbados',
        'BD' => 'Asia/Dhaka',
        'BE' => 'Europe/Brussels',
        'BF' => 'Africa/Ouagadougou',
        'BG' => 'Europe/Sofia',
        'BH' => 'Asia/Bahrain',
        'BI' => 'Africa/Bujumbura',
        'BJ' => 'Africa/Porto-Novo',
        'BM' => 'Atlantic/Bermuda',
        'BN' => 'Asia/Brunei',
        'BO' => 'America/La_Paz',
        'BR' => 'America/Araguaina',
        'BS' => 'America/Nassau',
        'BT' => 'Asia/Thimphu',
        'BW' => 'Africa/Gaborone',
        'BY' => 'Europe/Minsk',
        'BZ' => 'America/Belize',
        'CA' => 'America/Vancouver',
        'CC' => 'Indian/Cocos',
        'CD' => 'Africa/Kinshasa',
        'CF' => 'Africa/Bangui',
        'CG' => 'Africa/Brazzaville',
        'CH' => 'Europe/Zurich',
        'CI' => 'Africa/Abidjan',
        'CK' => 'Pacific/Rarotonga',
        'CL' => 'America/Santiago',
        'CM' => 'Africa/Douala',
        'CN' => 'Asia/Chongqing',
        'CO' => 'America/Bogota',
        'CR' => 'America/Costa_Rica',
        'CU' => 'America/Havana',
        'CV' => 'Atlantic/Cape_Verde',
        'CX' => 'Indian/Christmas',
        'CY' => 'Asia/Nicosia',
        'CZ' => 'Europe/Prague',
        'DE' => 'Europe/Berlin',
        'DJ' => 'Africa/Djibouti',
        'DK' => 'Europe/Copenhagen',
        'DM' => 'America/Dominica',
        'DO' => 'America/Santo_Domingo',
        'DZ' => 'Africa/Algiers',
        'EC' => 'America/Guayaquil',
        'EE' => 'Europe/Tallinn',
        'EG' => 'Africa/Cairo',
        'EH' => 'Africa/El_Aaiun',
        'ER' => 'Africa/Asmara',
        'ES' => 'Africa/Ceuta',
        'ET' => 'Africa/Addis_Ababa',
        'FI' => 'Europe/Helsinki',
        'FJ' => 'Pacific/Fiji',
        'FK' => 'Atlantic/Stanley',
        'FM' => 'Pacific/Kosrae',
        'FO' => 'Atlantic/Faroe',
        'FR' => 'Europe/Paris',
        'GA' => 'Africa/Libreville',
        'GB' => 'Europe/London',
        'GD' => 'America/Grenada',
        'GE' => 'Asia/Tbilisi',
        'GF' => 'America/Cayenne',
        'GG' => 'Europe/Guernsey',
        'GH' => 'Africa/Accra',
        'GI' => 'Europe/Gibraltar',
        'GL' => 'America/Danmarkshavn',
        'GM' => 'Africa/Banjul',
        'GN' => 'Africa/Conakry',
        'GP' => 'America/Guadeloupe',
        'GQ' => 'Africa/Malabo',
        'GR' => 'Europe/Athens',
        'GS' => 'Atlantic/South_Georgia',
        'GT' => 'America/Guatemala',
        'GU' => 'Pacific/Guam',
        'GW' => 'Africa/Bissau',
        'GY' => 'America/Guyana',
        'HK' => 'Asia/Hong_Kong',
        'HN' => 'America/Tegucigalpa',
        'HR' => 'Europe/Zagreb',
        'HT' => 'America/Port-au-Prince',
        'HU' => 'Europe/Budapest',
        'ID' => 'Asia/Jakarta',
        'IE' => 'Europe/Dublin',
        'IL' => 'Asia/Jerusalem',
        'IM' => 'Europe/Isle_of_Man',
        'IN' => 'Asia/Calcutta',
        'IO' => 'Indian/Chagos',
        'IQ' => 'Asia/Baghdad',
        'IR' => 'Asia/Tehran',
        'IS' => 'Atlantic/Reykjavik',
        'IT' => 'Europe/Rome',
        'JE' => 'Europe/Jersey',
        'JM' => 'America/Jamaica',
        'JO' => 'Asia/Amman',
        'JP' => 'Asia/Tokyo',
        'KE' => 'Africa/Nairobi',
        'KG' => 'Asia/Bishkek',
        'KH' => 'Asia/Phnom_Penh',
        'KI' => 'Pacific/Enderbury',
        'KM' => 'Indian/Comoro',
        'KN' => 'America/St_Kitts',
        'KP' => 'Asia/Pyongyang',
        'KR' => 'Asia/Seoul',
        'KW' => 'Asia/Kuwait',
        'KY' => 'America/Cayman',
        'KZ' => 'Asia/Almaty',
        'LA' => 'Asia/Vientiane',
        'LB' => 'Asia/Beirut',
        'LC' => 'America/St_Lucia',
        'LI' => 'Europe/Vaduz',
        'LK' => 'Asia/Colombo',
        'LR' => 'Africa/Monrovia',
        'LS' => 'Africa/Maseru',
        'LT' => 'Europe/Vilnius',
        'LU' => 'Europe/Luxembourg',
        'LV' => 'Europe/Riga',
        'LY' => 'Africa/Tripoli',
        'MA' => 'Africa/Casablanca',
        'MC' => 'Europe/Monaco',
        'MD' => 'Europe/Chisinau',
        'ME' => 'Europe/Podgorica',
        'MG' => 'Indian/Antananarivo',
        'MH' => 'Pacific/Kwajalein',
        'MK' => 'Europe/Skopje',
        'ML' => 'Africa/Bamako',
        'MM' => 'Asia/Rangoon',
        'MN' => 'Asia/Choibalsan',
        'MO' => 'Asia/Macau',
        'MP' => 'Pacific/Saipan',
        'MQ' => 'America/Martinique',
        'MR' => 'Africa/Nouakchott',
        'MS' => 'America/Montserrat',
        'MT' => 'Europe/Malta',
        'MU' => 'Indian/Mauritius',
        'MV' => 'Indian/Maldives',
        'MW' => 'Africa/Blantyre',
        'MX' => 'America/Mexico_City',
        'MY' => 'Asia/Kuala_Lumpur',
        'MZ' => 'Africa/Maputo',
        'NA' => 'Africa/Windhoek',
        'NC' => 'Pacific/Noumea',
        'NE' => 'Africa/Niamey',
        'NF' => 'Pacific/Norfolk',
        'NG' => 'Africa/Lagos',
        'NI' => 'America/Managua',
        'NL' => 'Europe/Amsterdam',
        'NO' => 'Europe/Oslo',
        'NP' => 'Asia/Katmandu',
        'NR' => 'Pacific/Nauru',
        'NU' => 'Pacific/Niue',
        'NZ' => 'Pacific/Auckland',
        'OM' => 'Asia/Muscat',
        'PA' => 'America/Panama',
        'PE' => 'America/Lima',
        'PF' => 'Pacific/Gambier',
        'PG' => 'Pacific/Port_Moresby',
        'PH' => 'Asia/Manila',
        'PK' => 'Asia/Karachi',
        'PL' => 'Europe/Warsaw',
        'PM' => 'America/Miquelon',
        'PN' => 'Pacific/Pitcairn',
        'PR' => 'America/Puerto_Rico',
        'PS' => 'Asia/Gaza',
        'PT' => 'Atlantic/Azores',
        'PW' => 'Pacific/Palau',
        'PY' => 'America/Asuncion',
        'QA' => 'Asia/Qatar',
        'RE' => 'Indian/Reunion',
        'RO' => 'Europe/Bucharest',
        'RS' => 'Europe/Belgrade',
        'RU' => 'Europe/Moscow',
        'RW' => 'Africa/Kigali',
        'SA' => 'Asia/Riyadh',
        'SB' => 'Pacific/Guadalcanal',
        'SC' => 'Indian/Mahe',
        'SD' => 'Africa/Khartoum',
        'SE' => 'Europe/Stockholm',
        'SG' => 'Asia/Singapore',
        'SH' => 'Atlantic/St_Helena',
        'SI' => 'Europe/Ljubljana',
        'SJ' => 'Arctic/Longyearbyen',
        'SK' => 'Europe/Bratislava',
        'SL' => 'Africa/Freetown',
        'SM' => 'Europe/San_Marino',
        'SN' => 'Africa/Dakar',
        'SO' => 'Africa/Mogadishu',
        'SR' => 'America/Paramaribo',
        'ST' => 'Africa/Sao_Tome',
        'SV' => 'America/El_Salvador',
        'SY' => 'Asia/Damascus',
        'SZ' => 'Africa/Mbabane',
        'TC' => 'America/Grand_Turk',
        'TD' => 'Africa/Ndjamena',
        'TF' => 'Indian/Kerguelen',
        'TG' => 'Africa/Lome',
        'TH' => 'Asia/Bangkok',
        'TJ' => 'Asia/Dushanbe',
        'TK' => 'Pacific/Fakaofo',
        'TL' => 'Asia/Dili',
        'TM' => 'Asia/Ashgabat',
        'TN' => 'Africa/Tunis',
        'TO' => 'Pacific/Tongatapu',
        'TR' => 'Europe/Istanbul',
        'TT' => 'America/Port_of_Spain',
        'TV' => 'Pacific/Funafuti',
        'TW' => 'Asia/Taipei',
        'TZ' => 'Africa/Dar_es_Salaam',
        'UA' => 'Europe/Kiev',
        'UG' => 'Africa/Kampala',
        'UM' => 'Pacific/Johnston',
        'US' => 'America/New_York',
        'UY' => 'America/Montevideo',
        'UZ' => 'Asia/Tashkent',
        'VA' => 'Europe/Vatican',
        'VC' => 'America/St_Vincent',
        'VE' => 'America/Caracas',
        'VG' => 'America/Tortola',
        'VI' => 'America/St_Thomas',
        'VN' => 'Asia/Saigon',
        'VU' => 'Pacific/Efate',
        'WF' => 'Pacific/Wallis',
        'WS' => 'Pacific/Apia',
        'YE' => 'Asia/Aden',
        'YT' => 'Indian/Mayotte',
        'ZA' => 'Africa/Johannesburg',
        'ZM' => 'Africa/Lusaka',
        'ZW' => 'Africa/Harare'
    ];

    /**
     * Constructor.
     *
     * @param ValidatorInterface $validator
     * @param SettingsResolver $settingsResolver
     * @param LoggerInterface    $logger
     */
    public function __construct(ValidatorInterface $validator, SettingsResolver $settingsResolver, LoggerInterface $logger)
    {
        $this->validator        = $validator;
        $this->settingsResolver = $settingsResolver;
        $this->logger           = $logger;
    }

    /**
     * @param string $originalNumber
     * @param string $countryName
     *
     * @return string
     */
    public function getFormattedNumber($originalNumber, $countryName = null)
    {
        $number = $originalNumber;
        if (!$number) {
            return '';
        }

        $number = trim($number);
        $number = preg_replace('#[^0-9]#', '', $number);

        if (!$number) {
            return '';
        }

        $numberUtil   = PhoneNumberUtil::getInstance();
        $parsedNumber = null;

        // collect possible calling codes
        $callingCodes = [];
        foreach (self::$callingCodes as $codeNum => $codeCountryName) {
            if ($countryName && strtolower($countryName) !== strtolower($codeCountryName)) {
                continue;
            }

            $callingCodes[] = $codeNum;
        }

        $tryPrefixes = ['', '+'];
        foreach ($callingCodes as $callingCode) {
            $tryPrefixes[] = '+'.$callingCode;
        }

        $parseNumber = function ($number) use ($numberUtil, $tryPrefixes) {
            foreach ($tryPrefixes as $countryCode) {
                try {
                    $checkNumber  = $countryCode.' '.$number;
                    $parsedNumber = $numberUtil->parse($checkNumber, null);

                    if ($numberUtil->isValidNumber($parsedNumber)) {
                        return $parsedNumber;
                    }
                } catch (\Exception $e) {
                }
            }

            return;
        };

        // parse the number as is
        $parsedNumber = $parseNumber($number);

        // check leading zero
        if (!$parsedNumber) {
            $tryNumber = $number;
            while (preg_match('#^0#', $tryNumber)) {
                $tryNumber = preg_replace('#^0#', '', $tryNumber);
                $parsedNumber = $parseNumber($tryNumber);
                if ($parsedNumber) {
                    break;
                }
            }
        }

        // check if leading zero is after country calling code
        if (!$parsedNumber) {
            foreach ($callingCodes as $callingCode) {
                $parsedNumber = $parseNumber(preg_replace("#^{$callingCode}(0+)#", '', $number));
                if ($parsedNumber) {
                    break;
                }
            }
        }

        if (!$parsedNumber) {
            $this->logger->warning("Unable to parse phone number `$originalNumber`.");

            return '';
        }
        if (!$numberUtil->isValidNumber($parsedNumber)) {
            $this->logger->warning("Phone number `$originalNumber` is not valid.");

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

        // missing scheme, e.g. `site.com`
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            $url = 'http://'.$url;
        }

        // scheme dupes, e.g. `http://http://site.com`
        $url = preg_replace('#(http(s|)://)+#', 'http$2://', $url);

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

        $timezone = $this->settingsResolver->getGlobalSettings()->get('importer_timezone', 'UTC');
        if ($timezone) {
            $date->setTimezone(new \DateTimeZone($timezone));
        }

        return $date->format('c');
    }

    /**
     * @param string $countryCode
     *
     * @return string
     */
    public function getTimezoneByCountryCode($countryCode)
    {
        return isset(self::$countryTimeZones[$countryCode]) ? self::$countryTimeZones[$countryCode] : 'UTC';
    }

    /**
     * @param string $countryName
     *
     * @return string
     */
    public function getTimezoneByCountryName($countryName)
    {
        $countryCode = Countries::getCodeFromCountry($countryName);
        if (!$countryCode) {
            return 'UTC';
        }

        return $this->getTimezoneByCountryCode($countryCode);
    }
}
