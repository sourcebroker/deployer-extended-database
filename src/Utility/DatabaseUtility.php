<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

/**
 * Class DatabaseUtility
 * @package SourceBroker\DeployerExtendedDatabase\Utility
 */
class DatabaseUtility
{

    /**
     * @param $databasesConf
     * @return array
     */
    public static function getTables($databasesConf)
    {
        $link = mysqli_connect($databasesConf['host'], $databasesConf['user'], $databasesConf['password'], $databasesConf['dbname']);
        $result = $link->query('SHOW TABLES');
        $allTables = [];
        while ($row = $result->fetch_row()) {
            $allTables[] = array_shift($row);
        }
        return $allTables;
    }
}
