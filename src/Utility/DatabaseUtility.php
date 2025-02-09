<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

class DatabaseUtility
{
    private function connect(array $dbConf): \mysqli
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

        if ($mysqli->connect_error) {
            throw new \RuntimeException('Connection error: ' . $mysqli->connect_error);
        }

        return $mysqli;
    }

    public function getTables(array $dbConf): array
    {
        $mysqli = $this->connect($dbConf);
        $result = $mysqli->query('SHOW TABLES');
        $allTables = [];
        while ($row = $result->fetch_row()) {
            $allTables[] = array_shift($row);
        }
        return $allTables;
    }

    public function getBigTables(array $dbConf, float $bigTableSizeThreshold): array
    {
        $mysqli = $this->connect($dbConf);
        $bigTablesQuery = 'SELECT table_name AS `Table`,
                                  round(((data_length + index_length) / 1024 / 1024), 2) `Size (MB)`
                           FROM information_schema.TABLES
                           WHERE table_schema = \'' . $mysqli->real_escape_string($dbConf['dbname']) . '\'
                           AND ((data_length + index_length) / 1024 / 1024) > ' . $mysqli->real_escape_string($bigTableSizeThreshold) . '
                           ORDER BY (data_length + index_length) DESC
                           LIMIT 100;';
        $result = $mysqli->query($bigTablesQuery);
        if (!$result) {
            throw new \RuntimeException('Query error: ' . $mysqli->error);
        }
        $bigTables = [];
        while ($row = $result->fetch_assoc()) {
            $bigTables[] = $row;
        }
        return $bigTables;
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

        return count($options) ? ' ' . implode(' ', $options) : '';
    }

    public static function getTemporaryMyCnfFile(
        array $dbConfig,
        string $localInstanceDatabaseStoragePathNormalised
    ): string {
        $tmpMyCnfFile = $localInstanceDatabaseStoragePathNormalised . 'tmp_mysql_defaults_file_' . date('YmdHis') . '.cnf';
        $content = "[client]\n";
        $content .= "host=\"{$dbConfig['host']}\"\n";
        $content .= "port=\"" . ((isset($dbConfig['port']) && $dbConfig['port']) ? $dbConfig['port'] : 3306) . "\"\n";
        $content .= "user=\"{$dbConfig['user']}\"\n";
        $content .= "password=\"{$dbConfig['password']}\"\n";
        file_put_contents($tmpMyCnfFile, $content);

        return $tmpMyCnfFile;
    }

}
