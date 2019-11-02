<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-pull
 */
task('db:pull', function () {
    if (null === input()->getArgument('stage')) {
        throw new GracefulShutdownException('The target instance is required for db:pull command.');
    }
    $sourceInstance = get('target_stage');
    $dumpCode = md5(microtime(true) . rand(0, 10000));

    $dl = get('local/bin/deployer');
    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter(output());
    runLocally($dl . ' db:export ' . $sourceInstance . ' --db-dumpcode=' . $dumpCode . ' ' . $verbosity);
    runLocally($dl . ' db:download ' . $sourceInstance . ' --db-dumpcode=' . $dumpCode . ' ' . $verbosity);
    runLocally($dl . ' db:process --db-dumpcode=' . $dumpCode . ' ' . $verbosity);
    runLocally($dl . ' db:import --db-dumpcode=' . $dumpCode . ' ' . $verbosity);
    runLocally($dl . ' db:compress --db-dumpcode=' . $dumpCode . ' ' . $verbosity);
    runLocally($dl . ' db:dumpclean' . $verbosity);
})->desc('Synchronize database from target instance to current instance');
