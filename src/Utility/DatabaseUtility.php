<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

class DatabaseUtility
{
    public function getTables(array $dbConf): array
    {
        $link = mysqli_connect($dbConf['host'], $dbConf['user'], $dbConf['password'], $dbConf['dbname'],
            $dbConf['port']);
        $result = $link->query('SHOW TABLES');
        $allTables = [];
        while ($row = $result->fetch_row()) {
            $allTables[] = array_shift($row);
        }
        return $allTables;
    }
}
