<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-truncate
 */
task('db:truncate', function () {
    $arrayUtility = new ArrayUtility();
    $databaseUtility = new DatabaseUtility();
    if (get('db_instance') == get('server')['name']) {
        foreach (get('db_databases_merged') as $databaseConfig) {
            if (isset($databaseConfig['truncate_tables']) && is_array($databaseConfig['truncate_tables'])) {
                $truncateTables = $arrayUtility->filterWithRegexp(
                    $databaseConfig['truncate_tables'],
                    $databaseUtility->getTables($databaseConfig)
                );
                if (!empty($truncateTables)) {
                    foreach ($truncateTables as $truncateTable) {
                        runLocally(sprintf(
                            'export MYSQL_PWD=%s && %s -h%s -P%s -u%s -D%s -e %s',
                            escapeshellarg($databaseConfig['password']),
                            get('local/bin/mysql'),
                            escapeshellarg($databaseConfig['host']),
                            escapeshellarg((isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306),
                            escapeshellarg($databaseConfig['user']),
                            escapeshellarg($databaseConfig['dbname']),
                            escapeshellarg('TRUNCATE ' . $truncateTable)
                        ), 0);
                    }
                    writeln('<info>Truncated tables: ' . implode(',', $truncateTables) . '</info>');
                } else {
                    throw new \Exception('Regexp filters for "truncate_tables" variable returned empty value.');
                }
            } else {
                throw new \Exception('Variable "truncate_tables" is empty.');
            }
        }
    } else {
        $verbosity = (new ConsoleUtility())->getVerbosityAsParameter(output());
        if (test('[ -L {{deploy_path}}/release ]')) {
            run('cd {{deploy_path}}/release && {{bin/php}} {{bin/deployer}} db:truncate ' . $verbosity);
        } else {
            run('cd {{deploy_path}}/current && {{bin/php}} {{bin/deployer}} db:truncate ' . $verbosity);
        }
    }
})->desc('Truncate tables defined in "truncate_tables" variable.');
