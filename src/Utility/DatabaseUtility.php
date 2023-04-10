<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

class DatabaseUtility
{
    public function getTables(array $dbConf): array
    {
        $mysqli = mysqli_init();

        $mysqli->ssl_set(
            $dbConf['ssl_key'] ?? null,
            $dbConf['ssl_cert'] ?? null,
            $dbConf['ssl_ca'] ?? null,
            $dbConf['ssl_capath'] ?? null,
            $dbConf['ssl_cipher'] ?? null
        );

        $mysqli->real_connect(
            $dbConf['host'],
            $dbConf['user'],
            $dbConf['password'],
            $dbConf['dbname'],
            $dbConf['port'],
            null,
            isset($dbConf['flags']) ? (int)$dbConf['flags'] : 0
        );

        $result = $mysqli->query('SHOW TABLES');
        $allTables = [];
        while ($row = $result->fetch_row()) {
            $allTables[] = array_shift($row);
        }
        return $allTables;
    }

    public static function getSslCliOptions(array $dbConfig): string
    {
        $options = [];

        if (isset($dbConfig['flags']) && (int)$dbConfig['flags'] === MYSQLI_CLIENT_SSL) {
            $options[] = '--ssl';
        }

        foreach (['ssl_key', 'ssl_cert', 'ssl_ca', 'ssl_capath', 'ssl_cipher'] as $option) {
            if (isset($dbConfig[$option])) {
                $options[] = '--' . str_replace('_', '-', $option) . '=' . escapeshellarg($dbConfig[$option]);
            }
        }

        return ' ' . implode(' ', $options);
    }
}
