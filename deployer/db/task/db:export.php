<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

task('db:export', function () {
    if (!empty(input()->getOption('dumpcode'))) {
        $returnDumpCode = false;
        $dumpCode = input()->getOption('dumpcode');
    } else {
        $returnDumpCode = true;
        $dumpCode = md5(microtime(true) . rand(0, 10000));
    }
    $fileUtility = new FileUtility();
    $arrayUtility = new ArrayUtility();
    $databaseUtility = new DatabaseUtility();
    if (get('db_instance') == get('server')['name']) {
        foreach (get('db_databases_merged') as $databaseCode => $databaseConfig) {
            $filenameParts = [
                'dateTime' => date('Y-m-d_H:i:s'),
                'server' => 'server:' . $fileUtility->normalizeFilename(get('server')['name']),
                'dbcode' => 'dbcode:' . $fileUtility->normalizeFilename($databaseCode),
                'dumpcode' => 'dumpcode:' . $fileUtility->normalizeFilename($dumpCode),
                'type' => '',
            ];
            $mysqlDumpArgs = [
                'password' => escapeshellarg($databaseConfig['password']),
                'local/bin/mysqldump' => get('local/bin/mysqldump'),
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
            $mysqlDumpArgs['type'] = $fileUtility->normalizeFolder(get('db_storage_path_current'))
                . '/' . implode('#', $filenameParts) . '.sql';
            runLocally(vsprintf(
                'export MYSQL_PWD=%s && %s %s -h%s -P%s -u%s %s -r %s',
                $mysqlDumpArgs
            ), 0);

            // dump database data
            if (isset($databaseConfig['ignore_tables_out']) && is_array($databaseConfig['ignore_tables_out'])) {
                $ignoreTables = $arrayUtility->filterWithRegexp(
                    $databaseConfig['ignore_tables_out'],
                    $databaseUtility->getTables($databaseConfig)
                );
                if (!empty($ignoreTables)) {
                    $mysqlDumpArgs['ignore-tables'] = '--ignore-table=' . $databaseConfig['dbname'] . '.' .
                        implode(' --ignore-table=' . $databaseConfig['dbname'] . '.', $ignoreTables);
                }
            }
            $filenameParts['type'] = 'type:data';
            $mysqlDumpArgs['options'] = get('db_export_mysqldump_options_data', '');
            $mysqlDumpArgs['type'] = $fileUtility->normalizeFolder(get('db_storage_path_current'))
                . implode('#', $filenameParts) . '.sql';
            runLocally(vsprintf(
                'export MYSQL_PWD=%s && %s %s -h%s -P%s -u%s %s -r %s %s',
                $mysqlDumpArgs
            ), 0);
        }
    } else {
        $verbosity = (new ConsoleUtility())->getVerbosity(output());
        if (test('[ -L {{deploy_path}}/release ]')) {
            run('cd {{deploy_path}}/release && {{bin/php}} {{bin/deployer}} db:export --dumpcode=' . $dumpCode . ' ' . $verbosity);
        } else {
            run('cd {{deploy_path}}/current && {{bin/php}} {{bin/deployer}} db:export --dumpcode=' . $dumpCode . ' ' . $verbosity);
        }
    }
    if ($returnDumpCode) {
        writeln(json_encode(['dumpCode' => $dumpCode]));
    }
})->desc('Export database dump to local database dumps storage.');
