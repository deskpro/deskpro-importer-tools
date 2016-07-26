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

use DeskPRO\ImporterTools\Importers\ZenDesk\ZenDeskReader;

/**
 * Class CustomFieldWidgetTypeMapper.
 */
class CustomFieldWidgetTypeMapper
{
    /**
     * Returns DeskPRO custom def handler_class by ZenDesk field type.
     *
     * @param string $zd_field_type
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public static function getHandlerClass($zd_field_type)
    {
        $mapping = self::getFieldTypesMapping();
        $type    = strtolower($zd_field_type);

        if (isset($mapping[$type])) {
            return $mapping[$type];
        }

        throw new \RuntimeException(sprintf('Custom def handler_class not found by `%s`', $zd_field_type));
    }

    /**
     * ZenDesk field types.
     *
     * @return array
     */
    public static function getFieldTypesMapping()
    {
        return [
            ZenDeskReader::FIELD_TYPE_SYSTEM_SUBJECT        => 'text',
            ZenDeskReader::FIELD_TYPE_SYSTEM_DESCRIPTION    => 'textarea',
            ZenDeskReader::FIELD_TYPE_SYSTEM_STATUS         => 'choice',
            ZenDeskReader::FIELD_TYPE_SYSTEM_TICKET_TYPE    => 'choice',
            ZenDeskReader::FIELD_TYPE_SYSTEM_BASIC_PRIORITY => 'choice',
            ZenDeskReader::FIELD_TYPE_SYSTEM_PRIORITY       => 'choice',
            ZenDeskReader::FIELD_TYPE_SYSTEM_GROUP          => 'choice',
            ZenDeskReader::FIELD_TYPE_SYSTEM_ASSIGNEE       => '',
            ZenDeskReader::FIELD_TYPE_CHECKBOX              => 'toggle',
            ZenDeskReader::FIELD_TYPE_TAGGER                => 'choice',
            ZenDeskReader::FIELD_TYPE_DATE                  => 'date',
            ZenDeskReader::FIELD_TYPE_DECIMAL               => 'text',
            ZenDeskReader::FIELD_TYPE_DROPDOWN              => 'choice',
            ZenDeskReader::FIELD_TYPE_INTEGER               => 'text',
            ZenDeskReader::FIELD_TYPE_REGEXP                => 'text',
            ZenDeskReader::FIELD_TYPE_TEXT                  => 'text',
            ZenDeskReader::FIELD_TYPE_TEXTAREA              => 'textarea',
        ];
    }
}
