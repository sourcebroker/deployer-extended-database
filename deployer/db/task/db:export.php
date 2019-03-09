<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-export
 */
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
    if (get('current_instance') == get('default_stage')) {
        foreach (get('db_databases_merged') as $databaseCode => $databaseConfig) {
            $filenameParts = [
                'dateTime' => date('Y-m-d_H-i-s'),
                'server' => 'server=' . $fileUtility->normalizeFilename(get('default_stage')),
                'dbcode' => 'dbcode=' . $fileUtility->normalizeFilename($databaseCode),
                'dumpcode' => 'dumpcode=' . $fileUtility->normalizeFilename($dumpCode),
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
                'absolutePath' => '',
                'ignore-tables' => ''
            ];

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
            // dump database structure
            $filenameParts['type'] = 'type=structure';
            $mysqlDumpArgs['options'] = get('db_export_mysqldump_options_structure', '');
            $mysqlDumpArgs['absolutePath'] = escapeshellarg($fileUtility->normalizeFolder(get('db_storage_path_current'))
                . implode('#', $filenameParts) . '.sql');
            runLocally(vsprintf(
                'export MYSQL_PWD=%s && %s %s -h%s -P%s -u%s %s -r%s'
                . ((new ConsoleUtility())->getOptionFromDboptions('exportTaskAddIgnoreTablesToStructureDump',
                    input()) ? ' %s' : ''),
                $mysqlDumpArgs
            ), 0);

            // dump database data
            $filenameParts['type'] = 'type=data';
            $mysqlDumpArgs['options'] = get('db_export_mysqldump_options_data', '');
            $mysqlDumpArgs['absolutePath'] = escapeshellarg($fileUtility->normalizeFolder(get('db_storage_path_current'))
                . implode('#', $filenameParts) . '.sql');
            runLocally(vsprintf(
                'export MYSQL_PWD=%s && %s %s -h%s -P%s -u%s %s -r%s %s',
                $mysqlDumpArgs
            ), 0);
        }
    } else {
        $verbosity = (new ConsoleUtility())->getVerbosityAsParameter(output());
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:export ' . (input()->getOption('dboptions') ? '--dboptions=' . input()->getOption('dboptions') : '') . ' --dumpcode=' . $dumpCode . ' ' . $verbosity);
    }
    if ($returnDumpCode) {
        writeln(json_encode(['dumpCode' => $dumpCode]));
    }
})->desc('Export database dumps to current database dumps storage.');
