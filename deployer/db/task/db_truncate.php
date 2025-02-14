<?php

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;
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
                    $databaseStoragePathLocal = get('db_storage_path_local');

                    $tmpMyCnfFile = DatabaseUtility::getTemporaryMyCnfFile(
                        $databaseConfig,
                        $databaseStoragePathLocal
                    );

                    try {
                        $truncateSql = implode('; ', array_map(function ($table) {
                                return 'TRUNCATE ' . $table;
                            }, $truncateTables)) . ';';

                        runLocally(sprintf(
                            '%s --defaults-file=%s -D%s %s -e %s',
                            get('local/bin/mysql'),
                            escapeshellarg($tmpMyCnfFile),
                            escapeshellarg($databaseConfig['dbname']),
                            DatabaseUtility::getSslCliOptions($databaseConfig),
                            escapeshellarg($truncateSql)
                        ));

                        if (output()->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                            info('Truncated tables: ' . implode(',', $truncateTables));
                        }

                    } catch (\Exception $exception) {
                        throw new GracefulShutdownException(
                            'Error during truncate. ' . $exception->getMessage(),
                            1500722095323
                        );
                    } finally {
                        unlink($tmpMyCnfFile);
                    }
                }
            }
        }
    } else {
        $verbosity = (new ConsoleUtility())->getVerbosityAsParameter();
        run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:truncate ' . get('argument_host') . $verbosity);
    }
})->desc('Truncate tables defined in "truncate_tables" variable');
