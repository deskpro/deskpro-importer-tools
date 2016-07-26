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

namespace DeskPRO\ImporterTools\Importers\ZenDesk\Mapper;

/**
 * Class TimeZoneMapper.
 */
class TimeZoneMapper
{
    /**
     * Returns DateTimeZone name by friendly name from ZenDesk object.
     *
     * @param string $friendly_name
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public static function getTimeZoneName($friendly_name)
    {
        $mapping = self::friendlyMapping();
        if (isset($mapping[$friendly_name])) {
            return $mapping[$friendly_name];
        }

        throw new \RuntimeException(sprintf('Timezone name not found by friendly name `%s`', $friendly_name));
    }

    /**
     * Returns the mapping from friendly name to DateTimeZone name.
     *
     * @return array
     */
    public static function friendlyMapping()
    {
        return [
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
    }
}
