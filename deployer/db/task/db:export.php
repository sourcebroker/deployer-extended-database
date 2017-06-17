<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;

task('db:export', function () {
    if (null !== input()->getArgument('stage')) {
        throw new \RuntimeException("You can not set targt instance for db:export command.");
    }
    $dumpCode = md5(microtime(true) . rand(0, 10000));
    $dateTime = date('Y-m-d_H:i:s');
    foreach (get('databases_config') as $databaseCode => $databaseConfig) {
        $filenameParts = [
            $dateTime,
            'server:' . FileUtility::normalizeFilename(get('server')['name']),
            'dbcode:' . FileUtility::normalizeFilename($databaseCode),
            'type',
            'dumpcode:' . $dumpCode,
        ];
        if (!file_exists(get('db_settings_storage_path'))) {
            mkdir(get('db_settings_storage_path'), 0755, true);
        }

        // dump database structure
        $filenameParts[3] = 'type:structure';
        runLocally(sprintf(
            'export MYSQL_PWD=%s && %s --no-data=true --default-character-set=utf8 -h%s -P%s -u%s %s -r %s',
            escapeshellarg($databaseConfig['password']),
            get('db_settings_mysqldump_path'),
            escapeshellarg($databaseConfig['host']),
            escapeshellarg((isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306),
            escapeshellarg($databaseConfig['user']),
            escapeshellarg($databaseConfig['dbname']),
            FileUtility::normalizeFolder(get('db_settings_storage_path')) . DIRECTORY_SEPARATOR . implode('#', $filenameParts) . '.sql'
        ), 0);

        // dump database data
        $ignoreTables = [];
        if(isset($databaseConfig['ignore_tables_out']) && is_array($databaseConfig['ignore_tables_out'])) {
            $allTables = DatabaseUtility::getTables($databaseConfig);
            $ignoreTables = ArrayUtility::filterWithRegexp($databaseConfig['ignore_tables_out'], $allTables);
        }
        $filenameParts[3] = 'type:data';
        runLocally(sprintf(
            'export MYSQL_PWD=%s && %s --create-options -e -K -q -n --default-character-set=utf8 -h%s -P%s -u%s %s -r %s %s',
            escapeshellarg($databaseConfig['password']),
            get('db_settings_mysqldump_path'),
            escapeshellarg($databaseConfig['host']),
            escapeshellarg((isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306),
            escapeshellarg($databaseConfig['user']),
            escapeshellarg($databaseConfig['dbname']),
            FileUtility::normalizeFolder(get('db_settings_storage_path')) . DIRECTORY_SEPARATOR . implode('#', $filenameParts) . '.sql',
            implode(' --ignore-table=' . $databaseConfig['dbname'] . '.', $ignoreTables)
        ), 0);
    }
    echo json_encode(['dumpCode' => $dumpCode]);
})->desc('Export database dump to local database dumps storage. Returns dumpcode as json.');
