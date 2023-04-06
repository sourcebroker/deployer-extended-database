<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

class DatabaseUtility
{
    public function getTables(array $dbConf): array
    {
        $mysqli = new \mysqli($dbConf['host'], $dbConf['user'], $dbConf['password'], $dbConf['dbname'],
            $dbConf['port']);

        $mysqli->ssl_set(
            $dbConf['ssl_key'] ?? null,
            $dbConf['ssl_cert'] ?? null,
            $dbConf['ssl_ca'] ?? null,
            $dbConf['ssl_capath'] ?? null,
            $dbConf['ssl_cipher'] ?? null
        );

        foreach ($dbConf['options'] ?? [] as $optionName => $value) {
            $mysqli->options($optionName, $value);
        }

        $result = $mysqli->query('SHOW TABLES');
        $allTables = [];
        while ($row = $result->fetch_row()) {
            $allTables[] = array_shift($row);
        }
        return $allTables;
    }
}
