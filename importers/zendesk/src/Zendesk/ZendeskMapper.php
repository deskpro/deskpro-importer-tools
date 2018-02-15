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

namespace DeskPRO\ImporterTools\Importers\Zendesk;

/**
 * Class ZendeskMapper.
 */
class ZendeskMapper
{
    public static $customFieldWidgetTypeMapping = [
        ZendeskReader::FIELD_TYPE_SYSTEM_SUBJECT        => 'text',
        ZendeskReader::FIELD_TYPE_SYSTEM_DESCRIPTION    => 'textarea',
        ZendeskReader::FIELD_TYPE_SYSTEM_STATUS         => 'choice',
        ZendeskReader::FIELD_TYPE_SYSTEM_TICKET_TYPE    => 'choice',
        ZendeskReader::FIELD_TYPE_SYSTEM_BASIC_PRIORITY => 'choice',
        ZendeskReader::FIELD_TYPE_SYSTEM_PRIORITY       => 'choice',
        ZendeskReader::FIELD_TYPE_SYSTEM_GROUP          => 'choice',
        ZendeskReader::FIELD_TYPE_SYSTEM_ASSIGNEE       => '',
        ZendeskReader::FIELD_TYPE_CHECKBOX              => 'toggle',
        ZendeskReader::FIELD_TYPE_TAGGER                => 'choice',
        ZendeskReader::FIELD_TYPE_DATE                  => 'date',
        ZendeskReader::FIELD_TYPE_DECIMAL               => 'text',
        ZendeskReader::FIELD_TYPE_DROPDOWN              => 'choice',
        ZendeskReader::FIELD_TYPE_INTEGER               => 'text',
        ZendeskReader::FIELD_TYPE_REGEXP                => 'text',
        ZendeskReader::FIELD_TYPE_TEXT                  => 'text',
        ZendeskReader::FIELD_TYPE_TEXTAREA              => 'textarea',
    ];

    public static $localeMapping = [
        'ar-eg'  => 'ar',    // Arabic (Egypt)
        'ar'     => 'ar',    // Arabic
        'ms'     => '?',     // Bahasa Melayu
        'ca'     => '?',     // Català (Catalan)
        'sr-me'  => '?',     // Crnogorski (Montenegrin)
        'da'     => 'da',    // Dansk
        'de'     => 'de',    // Deutsch
        'de-at'  => 'de',    // Deutsch (Austria)
        'de-ch'  => 'de',    // Deutsch (Switzerland)
        'et'     => '?',     // Eesti keel (Estonian)
        'en-us'  => 'en_US', // English
        'en-au'  => 'en_US', // English (AU)
        'en-ca'  => 'en_US', // English (Canada)
        'en-ie'  => 'en_US', // English (IE)
        'en-gb'  => 'en_GB', // English (UK)
        'es'     => 'es_ES', // Español
        'es-es'  => 'es_ES', // Español (España)
        'es-419' => 'es_ES', // Español (Latinoamérica)
        'fil'    => '?',     // Filipino
        'fr'     => 'fr',    // Français
        'fr-be'  => 'fr',    // Français (Belgium)
        'fr-ca'  => 'fr',    // Français (Canada)
        'fr-ch'  => 'fr',    // Français (Switzerland)
        'hr'     => '?',     // Hrvatski
        'id'     => '?',     // Indonesian
        'it'     => 'it',    // Italiano
        'lv'     => '?',     // Latvian
        'lt'     => '?',     // Lietuvių kalba
        'hu'     => 'hu',    // Magyar
        'nl-be'  => 'nl',    // Nederlands (Belgium)
        'nl'     => 'nl',    // Nederlands (Dutch)
        'no'     => 'no',    // Norsk
        'pl'     => 'pl',    // Polski (Polish)
        'pt-br'  => 'pt',    // Português (Brasil)
        'pt'     => 'pt',    // Português (Portugal)
        'ro'     => 'ro',    // Romana
        'sk'     => 'sk',    // Slovak
        'sl'     => '?',     // Slovenian
        'sr'     => '?',     // Srpski
        'fi'     => 'fi',    // Suomi (Finnish)
        'sv'     => 'sv',    // Svenska
        'th'     => '?',     // Thai (ไทย)
        'tr'     => 'tr',    // Türkçe
        'vi'     => '?',     // Vietnamese
        'is'     => '?',     // Íslenska
        'cs'     => '?',     // Čeština
        'el'     => '?',     // Ελληνικά (Greek)
        'ru'     => 'ru',    // Русский
        'uk'     => 'ru',    // Українська
        'he'     => '?',     // Hebrew
        'hi'     => '?',     // हिंदी
        'ja'     => 'ja',    // 日本語 (Japanese)
        'zh-cn'  => '?',     // 简体中文 (Simplified Chinese)
        'zh-tw'  => '?',     // 繁體中文 (Traditional Chinese)
        'ko'     => 'ko',    // 한국어 (Korean)
    ];

    public static $timezoneMapping = [
        'International Date Line West' => 'Pacific/Midway',
        'Midway Island'                => 'Pacific/Midway',
        'American Samoa'               => 'Pacific/Pago_Pago',
        'Hawaii'                       => 'Pacific/Honolulu',
        'Alaska'                       => 'America/Juneau',
        'Pacific Time (US & Canada)'   => 'America/Los_Angeles',
        'Tijuana'                      => 'America/Tijuana',
        'Mountain Time (US & Canada)'  => 'America/Denver',
        'Arizona'                      => 'America/Phoenix',
        'Chihuahua'                    => 'America/Chihuahua',
        'Mazatlan'                     => 'America/Mazatlan',
        'Central Time (US & Canada)'   => 'America/Chicago',
        'Saskatchewan'                 => 'America/Regina',
        'Guadalajara'                  => 'America/Mexico_City',
        'Mexico City'                  => 'America/Mexico_City',
        'Monterrey'                    => 'America/Monterrey',
        'Central America'              => 'America/Guatemala',
        'Eastern Time (US & Canada)'   => 'America/New_York',
        'Indiana (East)'               => 'America/Indiana/Indianapolis',
        'Bogota'                       => 'America/Bogota',
        'Lima'                         => 'America/Lima',
        'Quito'                        => 'America/Lima',
        'Atlantic Time (Canada)'       => 'America/Halifax',
        'Caracas'                      => 'America/Caracas',
        'La Paz'                       => 'America/La_Paz',
        'Santiago'                     => 'America/Santiago',
        'Newfoundland'                 => 'America/St_Johns',
        'Brasilia'                     => 'America/Sao_Paulo',
        'Buenos Aires'                 => 'America/Argentina/Buenos_Aires',
        'Montevideo'                   => 'America/Montevideo',
        'Georgetown'                   => 'America/Guyana',
        'Greenland'                    => 'America/Godthab',
        'Mid-Atlantic'                 => 'Atlantic/South_Georgia',
        'Azores'                       => 'Atlantic/Azores',
        'Cape Verde Is.'               => 'Atlantic/Cape_Verde',
        'Dublin'                       => 'Europe/Dublin',
        'Edinburgh'                    => 'Europe/London',
        'Lisbon'                       => 'Europe/Lisbon',
        'London'                       => 'Europe/London',
        'Casablanca'                   => 'Africa/Casablanca',
        'Monrovia'                     => 'Africa/Monrovia',
        'UTC'                          => 'Etc/UTC',
        'Belgrade'                     => 'Europe/Belgrade',
        'Bratislava'                   => 'Europe/Bratislava',
        'Budapest'                     => 'Europe/Budapest',
        'Ljubljana'                    => 'Europe/Ljubljana',
        'Prague'                       => 'Europe/Prague',
        'Sarajevo'                     => 'Europe/Sarajevo',
        'Skopje'                       => 'Europe/Skopje',
        'Warsaw'                       => 'Europe/Warsaw',
        'Zagreb'                       => 'Europe/Zagreb',
        'Brussels'                     => 'Europe/Brussels',
        'Copenhagen'                   => 'Europe/Copenhagen',
        'Madrid'                       => 'Europe/Madrid',
        'Paris'                        => 'Europe/Paris',
        'Amsterdam'                    => 'Europe/Amsterdam',
        'Berlin'                       => 'Europe/Berlin',
        'Bern'                         => 'Europe/Berlin',
        'Rome'                         => 'Europe/Rome',
        'Stockholm'                    => 'Europe/Stockholm',
        'Vienna'                       => 'Europe/Vienna',
        'West Central Africa'          => 'Africa/Algiers',
        'Bucharest'                    => 'Europe/Bucharest',
        'Cairo'                        => 'Africa/Cairo',
        'Helsinki'                     => 'Europe/Helsinki',
        'Kyiv'                         => 'Europe/Kiev',
        'Riga'                         => 'Europe/Riga',
        'Sofia'                        => 'Europe/Sofia',
        'Tallinn'                      => 'Europe/Tallinn',
        'Vilnius'                      => 'Europe/Vilnius',
        'Athens'                       => 'Europe/Athens',
        'Istanbul'                     => 'Europe/Istanbul',
        'Minsk'                        => 'Europe/Minsk',
        'Jerusalem'                    => 'Asia/Jerusalem',
        'Harare'                       => 'Africa/Harare',
        'Pretoria'                     => 'Africa/Johannesburg',
        'Moscow'                       => 'Europe/Moscow',
        'St. Petersburg'               => 'Europe/Moscow',
        'Volgograd'                    => 'Europe/Moscow',
        'Kuwait'                       => 'Asia/Kuwait',
        'Riyadh'                       => 'Asia/Riyadh',
        'Nairobi'                      => 'Africa/Nairobi',
        'Baghdad'                      => 'Asia/Baghdad',
        'Tehran'                       => 'Asia/Tehran',
        'Abu Dhabi'                    => 'Asia/Muscat',
        'Muscat'                       => 'Asia/Muscat',
        'Baku'                         => 'Asia/Baku',
        'Tbilisi'                      => 'Asia/Tbilisi',
        'Yerevan'                      => 'Asia/Yerevan',
        'Kabul'                        => 'Asia/Kabul',
        'Ekaterinburg'                 => 'Asia/Yekaterinburg',
        'Islamabad'                    => 'Asia/Karachi',
        'Karachi'                      => 'Asia/Karachi',
        'Tashkent'                     => 'Asia/Tashkent',
        'Chennai'                      => 'Asia/Kolkata',
        'Kolkata'                      => 'Asia/Kolkata',
        'Mumbai'                       => 'Asia/Kolkata',
        'New Delhi'                    => 'Asia/Kolkata',
        'Kathmandu'                    => 'Asia/Kathmandu',
        'Astana'                       => 'Asia/Dhaka',
        'Dhaka'                        => 'Asia/Dhaka',
        'Sri Jayawardenepura'          => 'Asia/Colombo',
        'Almaty'                       => 'Asia/Almaty',
        'Novosibirsk'                  => 'Asia/Novosibirsk',
        'Rangoon'                      => 'Asia/Rangoon',
        'Bangkok'                      => 'Asia/Bangkok',
        'Hanoi'                        => 'Asia/Bangkok',
        'Jakarta'                      => 'Asia/Jakarta',
        'Krasnoyarsk'                  => 'Asia/Krasnoyarsk',
        'Beijing'                      => 'Asia/Shanghai',
        'Chongqing'                    => 'Asia/Chongqing',
        'Hong Kong'                    => 'Asia/Hong_Kong',
        'Urumqi'                       => 'Asia/Urumqi',
        'Kuala Lumpur'                 => 'Asia/Kuala_Lumpur',
        'Singapore'                    => 'Asia/Singapore',
        'Taipei'                       => 'Asia/Taipei',
        'Perth'                        => 'Australia/Perth',
        'Irkutsk'                      => 'Asia/Irkutsk',
        'Ulaanbaatar'                  => 'Asia/Ulaanbaatar',
        'Seoul'                        => 'Asia/Seoul',
        'Osaka'                        => 'Asia/Tokyo',
        'Sapporo'                      => 'Asia/Tokyo',
        'Tokyo'                        => 'Asia/Tokyo',
        'Yakutsk'                      => 'Asia/Yakutsk',
        'Darwin'                       => 'Australia/Darwin',
        'Adelaide'                     => 'Australia/Adelaide',
        'Canberra'                     => 'Australia/Melbourne',
        'Melbourne'                    => 'Australia/Melbourne',
        'Sydney'                       => 'Australia/Sydney',
        'Brisbane'                     => 'Australia/Brisbane',
        'Hobart'                       => 'Australia/Hobart',
        'Vladivostok'                  => 'Asia/Vladivostok',
        'Guam'                         => 'Pacific/Guam',
        'Port Moresby'                 => 'Pacific/Port_Moresby',
        'Magadan'                      => 'Asia/Magadan',
        'Solomon Is.'                  => 'Pacific/Guadalcanal',
        'New Caledonia'                => 'Pacific/Noumea',
        'Fiji'                         => 'Pacific/Fiji',
        'Kamchatka'                    => 'Asia/Kamchatka',
        'Marshall Is.'                 => 'Pacific/Majuro',
        'Auckland'                     => 'Pacific/Auckland',
        'Wellington'                   => 'Pacific/Auckland',
        "Nuku'alofa"                   => 'Pacific/Tongatapu',
        'Tokelau Is.'                  => 'Pacific/Fakaofo',
        'Chatham Is.'                  => 'Pacific/Chatham',
        'Samoa'                        => 'Pacific/Apia',
    ];

    /**
     * @param string$locale
     *
     * @return null|string
     */
    public static function getLanguageByLocale($locale)
    {
        if (!$locale) {
            return;
        }

        if (is_array($locale)) {
            $locale = reset($locale);
        }

        if (!isset(self::$localeMapping[$locale])) {
            return;
        }

        $language = self::$localeMapping[$locale];

        return $language !== '?' ? $language : null;
    }
}
