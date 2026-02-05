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
        string $localInstanceDatabaseStoragePath
    ): string {
        $tmpMyCnfFile = $localInstanceDatabaseStoragePath . 'tmp_mysql_defaults_file_' . date('YmdHis') . '.cnf';
        $content = "[client]\n";
        $content .= "host=\"{$dbConfig['host']}\"\n";
        $content .= "port=\"" . ((isset($dbConfig['port']) && $dbConfig['port']) ? $dbConfig['port'] : 3306) . "\"\n";
        $content .= "user=\"{$dbConfig['user']}\"\n";
        $content .= "password=\"{$dbConfig['password']}\"\n";
        $content .= "[client-mariadb]\n";
        $content .= "disable-ssl-verify-server-cert\n";
        file_put_contents($tmpMyCnfFile, $content);

        return $tmpMyCnfFile;
    }

    public function getDumpFiles(
        string $localInstanceDatabaseStoragePath,
        array $filters = [],
        array $fileTypes = ['sql', 'gz'],
        array $sort = []
    ): array {
        $dumpFiles = [];
        foreach ($fileTypes as $type) {
            $dumpFiles = [...$dumpFiles, ...glob($localInstanceDatabaseStoragePath . '*.' . $type)];
        }
        if ($dumpFiles) {
            $fileUtility = new FileUtility();

            foreach ($filters as $key => $value) {
                $dumpFiles = array_filter($dumpFiles, static function ($file) use ($fileUtility, $key, $value) {
                    return $fileUtility->getDumpFilenamePart(basename($file), $key) === $value;
                });
            }

            if (isset($filters['tags'])) {
                $tags = explode('+', $filters['tags']);
                $dumpFiles = array_filter($dumpFiles, static function ($file) use ($fileUtility, $tags) {
                    $fileTags = explode('+', $fileUtility->getDumpFilenamePart(basename($file), 'tags'));
                    return !array_diff($tags, $fileTags);
                });
            }

            if (isset($sort['dateTime'])) {
                usort($dumpFiles, static function ($a, $b) use ($fileUtility, $sort) {
                    $dateTimeA = $fileUtility->getDumpFilenamePart(basename($a), 'dateTime');
                    $dateTimeB = $fileUtility->getDumpFilenamePart(basename($b), 'dateTime');
                    return $sort['dateTime'] === 'asc' ? $dateTimeA <=> $dateTimeB : $dateTimeB <=> $dateTimeA;
                });
            }
        }
        return $dumpFiles;
    }

    public function getDumpFile(
        string $localInstanceDatabaseStoragePath,
        array $filters = [],
        array $fileTypes = ['sql', 'gz']
    ): ?string {
        $dumpFiles = $this->getDumpFiles($localInstanceDatabaseStoragePath, $filters, $fileTypes);
        return !empty($dumpFiles) ? reset($dumpFiles) : null;
    }

    public function getLastDumpFile(
        string $localInstanceDatabaseStoragePath,
        array $filters = [],
        array $fileTypes = ['sql', 'gz']
    ): ?string {
        $dumpFiles = $this->getDumpFiles($localInstanceDatabaseStoragePath, $filters, $fileTypes,
            ['dateTime' => 'asc']);
        return !empty($dumpFiles) ? end($dumpFiles) : null;
    }

    public function removeDumpFiles(
        string $localInstanceDatabaseStoragePath,
        array $filters = [],
        array $fileTypes = ['sql', 'gz'],
        array $sort = []
    ): void {
        $dumpFiles = $this->getDumpFiles($localInstanceDatabaseStoragePath, $filters, $fileTypes, $sort);
        foreach ($dumpFiles as $file) {
            unlink($file);
        }
    }

}
