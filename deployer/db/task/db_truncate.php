<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-truncate
 */
task('db:truncate', function () {
    $arrayUtility = new ArrayUtility();
    $databaseUtility = new DatabaseUtility();
    if (get('is_argument_host_the_same_as_local_host')) {
        foreach (get('db_databases_merged') as $databaseConfig) {
            if (isset($databaseConfig['truncate_tables']) && is_array($databaseConfig['truncate_tables'])) {
                $truncateTables = $arrayUtility->filterWithRegexp(
                    $databaseConfig['truncate_tables'],
                    $databaseUtility->getTables($databaseConfig)
                );
                if (!empty($truncateTables)) {
                    foreach ($truncateTables as $truncateTable) {
                        runLocally(sprintf(
                            'export MYSQL_PWD=%s && %s -h%s -P%s -u%s -D%s %s -e %s',
                            escapeshellarg($databaseConfig['password']),
                            get('local/bin/mysql'),
                            escapeshellarg($databaseConfig['host']),
                            escapeshellarg((isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306),
                            escapeshellarg($databaseConfig['user']),
                            escapeshellarg($databaseConfig['dbname']),
                            DatabaseUtility::getSslCliOptions($databaseConfig),
                            escapeshellarg('TRUNCATE ' . $truncateTable)
                        ));
                    }
                    if (output()->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                        info('Truncated tables: ' . implode(',', $truncateTables));
                    }
                }
            }
        }
    } else {
        $verbosity = (new ConsoleUtility())->getVerbosityAsParameter();
        run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:truncate ' . get('argument_host') . $verbosity);
    }
})->desc('Truncate tables defined in "truncate_tables" variable');
