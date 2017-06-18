<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;

task('db:truncate', function () {
    if (get('db_instance') == get('server')['name']) {
        foreach (get('db_databases_merged') as $databaseCode => $databaseConfig) {
            $cachingTablesWithPatterns = $databaseConfig['truncate_tables'];
            $cachingTables = ArrayUtility::filterWithRegexp(
                $cachingTablesWithPatterns,
                DatabaseUtility::getTables($databaseConfig)
            );
            foreach ($cachingTables as $cachingTable) {
                runLocally(sprintf(
                    'export MYSQL_PWD=%s && %s --default-character-set=utf8 -h%s -P%s -u%s -D%s -e \'%s\' ',
                    escapeshellarg($databaseConfig['password']),
                    get('bin/mysql'),
                    $databaseConfig['host'],
                    (isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306,
                    $databaseConfig['user'],
                    $databaseConfig['dbname'],
                    'TRUNCATE ' . $cachingTable), 0);
            }
            writeln('<info>Truncated tables: ' . implode(',', $cachingTables) . '</info>');
        };
    } else {
        if (test('[ -L {{deploy_path}}/release ]')) {
            run("cd {{deploy_path}}/release && {{bin/php}} {{bin/deployer}} -q db:truncate");
        } else {
            run("cd {{deploy_path}}/current && {{bin/php}} {{bin/deployer}} -q db:truncate");
        }
    }
})->desc('Truncate tables defined as caching tables.');
