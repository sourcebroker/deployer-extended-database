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
    if (empty(get('argument_stage'))) {
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
                        ));
                    }
                    writeln('<info>Truncated tables: ' . implode(',', $truncateTables) . '</info>');
                }
            }
        }
    } else {
        $verbosity = (new ConsoleUtility())->getVerbosityAsParameter();
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:truncate ' . $verbosity);
    }
})->desc('Truncate tables defined in "truncate_tables" variable');
