<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;

task('db:export', function () {
    if (input()->getOption('dumpcode')) {
        $returnDumpCode = false;
        $dumpCode = input()->getOption('dumpcode');
    } else {
        $returnDumpCode = true;
        $dumpCode = md5(microtime(true) . rand(0, 10000));
    }
    if (get('db_instance') == get('server')['name']) {
        $dateTime = date('Y-m-d_H:i:s');
        foreach (get('db_databases_merged') as $databaseCode => $databaseConfig) {
            $filenameParts = [
                'dateTime' => $dateTime,
                'server' => 'server:' . FileUtility::normalizeFilename(get('server')['name']),
                'dbcode' => 'dbcode:' . FileUtility::normalizeFilename($databaseCode),
                'type' => '',
                'dumpcode' => 'dumpcode:' . FileUtility::normalizeFilename($dumpCode),
            ];
            $mysqlDumpArgs = [
                'password' => escapeshellarg($databaseConfig['password']),
                'bin/mysqldump' => get('bin/mysqldump'),
                'options' => '',
                'host' => escapeshellarg($databaseConfig['host']),
                'port' => escapeshellarg((isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306),
                'user' => escapeshellarg($databaseConfig['user']),
                'dbname' => escapeshellarg($databaseConfig['dbname']),
                'type' => '',
                'ignore-tables' => ''
            ];

            // dump database structure
            $filenameParts['type'] = 'type:structure';
            $mysqlDumpArgs['options'] = get('db_export_mysqldump_options_structure', '');
            $mysqlDumpArgs['type'] = FileUtility::normalizeFolder(get('db_storage_path_current'))
                . '/' . implode('#', $filenameParts) . '.sql';
            runLocally(vsprintf(
                'export MYSQL_PWD=%s && %s %s -h%s -P%s -u%s %s -r %s',
                $mysqlDumpArgs
            ), 0);

            // dump database data
            if (isset($databaseConfig['ignore_tables_out']) && is_array($databaseConfig['ignore_tables_out'])) {
                $ignoreTables = ArrayUtility::filterWithRegexp(
                    $databaseConfig['ignore_tables_out'],
                    DatabaseUtility::getTables($databaseConfig)
                );
                if (!empty($ignoreTables)) {
                    $mysqlDumpArgs['ignore-tables'] = '--ignore-table=' . $databaseConfig['dbname'] . '.' . implode(' --ignore-table=' . $databaseConfig['dbname'] . '.',
                            $ignoreTables);
                }
            }
            $filenameParts['type'] = 'type:data';
            $mysqlDumpArgs['options'] = get('db_export_mysqldump_options_data', '');
            $mysqlDumpArgs['type'] = FileUtility::normalizeFolder(get('db_storage_path_current'))
                . '/' . implode('#', $filenameParts) . '.sql';
            runLocally(vsprintf(
                'export MYSQL_PWD=%s && %s %s -h%s -P%s -u%s %s -r %s %s',
                $mysqlDumpArgs
            ), 0);
        }
        if ($returnDumpCode) {
            echo json_encode(['dumpCode' => $dumpCode]);
        }
    } else {
        if (test('[ -L {{deploy_path}}/release ]')) {
            run("cd {{deploy_path}}/release && {{bin/php}} {{bin/deployer}} db:export --dumpcode=" . $dumpCode);
        } else {
            run("cd {{deploy_path}}/current && {{bin/php}} {{bin/deployer}} db:export --dumpcode=" . $dumpCode);
        }
    }
})->desc('Export database dump to local database dumps storage.');
