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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DbHelper.
 */
class DbHelper
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $credentials;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $credentials
     */
    public function setCredentials(array $credentials)
    {
        $this->credentials = $credentials;
        $this->connection  = DriverManager::getConnection($this->credentials);
    }

    /**
     * @param string $tableName
     *
     * @return bool
     */
    public function tableExists($tableName)
    {
        return $this->connection->getSchemaManager()->tablesExist([$tableName]);
    }

    /**
     * @param string $tableName
     * @param string $column
     *
     * @return bool
     */
    public function columnExists($tableName, $column)
    {
        $result = $this->connection->executeQuery("SHOW COLUMNS FROM `$tableName` LIKE '$column'");

        return $result->rowCount() > 0;
    }

    /**
     * @param string $query
     * @param array  $params
     * @param int    $perPage
     *
     * @return DbPager
     */
    public function getPager($query, array $params = [], $perPage = 1000)
    {
        return DbPager::getIterator(new DbPager($this->connection, $query, $params, $perPage));
    }

    /**
     * @param string $query
     * @param array  $params
     *
     * @return mixed
     */
    public function findOne($query, array $params = [])
    {
        $statement = $this->connection->executeQuery($query, $params, self::getParamTypes($params));

        return $statement->fetch();
    }

    /**
     * @param string $query
     * @param array  $params
     *
     * @return mixed
     */
    public function findAll($query, array $params = [])
    {
        $statement = $this->connection->executeQuery($query, $params, self::getParamTypes($params));

        return $statement->fetchAll();
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public static function getParamTypes(array $params = [])
    {
        $types = [];
        foreach ($params as $name => $param) {
            if (is_array($param)) {
                $types[$name] = Connection::PARAM_INT_ARRAY;
            } else {
                $types[$name] = \PDO::PARAM_STR;
            }
        }

        return $types;
    }
}
