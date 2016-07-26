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

namespace DeskPRO\ImporterTools\Importers\ZenDesk\Request\ClientHelper\HelpCenter;

use DeskPRO\ImporterTools\Importers\ZenDesk\Request\ClientHelper\AbstractHelper;
use Zendesk\API\Exceptions\MissingParametersException;

/**
 * ZenDesk HelpCenter category translations request client helper.
 *
 * Class CategoryTranslation
 */
class CategoryTranslation extends AbstractHelper
{
    /**
     * {@inheritdoc}
     *
     * @see https://developer.zendesk.com/rest_api/docs/help_center/translations#create-translation
     */
    public function create(array $params = [])
    {
        if (!isset($params['id'])) {
            throw new MissingParametersException(__METHOD__, ['id']);
        }

        $category_id = $params['id'];
        unset($params['id']);

        return $this->doPostRequest(sprintf('help_center/categories/%d/translations.json', $category_id), $params);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://developer.zendesk.com/rest_api/docs/help_center/translations#list-translations
     */
    public function findAll(array $params = [])
    {
        if (!isset($params['id'])) {
            throw new MissingParametersException(__METHOD__, ['id']);
        }

        return $this->doGetRequest(sprintf('help_center/categories/%d/translations.json', $params['id']));
    }
}
