<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;

task('db:export', function () {
    if (null !== input()->getArgument('stage')) {
        throw new \RuntimeException("You can not set target instance for db:export command. It can be run only for current instance.");
    }
    if (input()->getOption('dumpcode')) {
        $returnDumpCode = false;
        $dumpCode = input()->getOption('dumpcode');
    } else {
        $returnDumpCode = true;
        $dumpCode = md5(microtime(true) . rand(0, 10000));
    }
    $dateTime = date('Y-m-d_H:i:s');
    foreach (get('databases_config') as $databaseCode => $databaseConfig) {
        $filenameParts = [
            $dateTime,
            'server:' . FileUtility::normalizeFilename(get('server')['name']),
            'dbcode:' . FileUtility::normalizeFilename($databaseCode),
            'type',
            'dumpcode:' . FileUtility::normalizeFilename($dumpCode),
        ];
        if (!file_exists(get('db_settings_storage_path'))) {
            mkdir(get('db_settings_storage_path'), 0755, true);
        }
        $mysqlDumpArgs = [
            'password' => escapeshellarg($databaseConfig['password']),
            'db_settings_mysqldump_path' => get('db_settings_mysqldump_path'),
            'host' => escapeshellarg($databaseConfig['host']),
            'port' => escapeshellarg((isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306),
            'user' => escapeshellarg($databaseConfig['user']),
            'dbname' => escapeshellarg($databaseConfig['dbname']),
            'type' => ''
        ];
        // dump database structure
        $filenameParts[3] = 'type:structure';
        $mysqlDumpArgs['type'] = FileUtility::normalizeFolder(get('db_settings_storage_path')) . '/' . implode('#', $filenameParts) . '.sql';
        runLocally(vsprintf(
            'export MYSQL_PWD=%s && %s --no-data=true --default-character-set=utf8 -h%s -P%s -u%s %s -r %s',
            $mysqlDumpArgs
        ), 0);

        // dump database data
        $ignoreTables = [];
        if (isset($databaseConfig['ignore_tables_out']) && is_array($databaseConfig['ignore_tables_out'])) {
            $allTables = DatabaseUtility::getTables($databaseConfig);
            $ignoreTables = ArrayUtility::filterWithRegexp($databaseConfig['ignore_tables_out'], $allTables);
        }
        $filenameParts[3] = 'type:data';
        $mysqlDumpArgs['type'] = FileUtility::normalizeFolder(get('db_settings_storage_path')) . '/' . implode('#', $filenameParts) . '.sql';
        $mysqlDumpArgs[] = implode(' --ignore-table=' . $databaseConfig['dbname'] . '.', $ignoreTables);
        runLocally(vsprintf(
            'export MYSQL_PWD=%s && %s --create-options -e -K -q -n --default-character-set=utf8 -h%s -P%s -u%s %s -r %s %s',
            $mysqlDumpArgs
        ), 0);
    }
    if($returnDumpCode) {
        echo json_encode(['dumpCode' => $dumpCode]);
    }
})->desc('Export database dump to local database dumps storage. Returns dumpcode as json.');
