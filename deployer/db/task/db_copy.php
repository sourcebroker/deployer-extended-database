<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-copy
 */
task('db:copy', function () {
    if (null === input()->getArgument('stage')) {
        throw new GracefulShutdownException("The source instance is required for db:move command.");
    }
    if (input()->getOption('db-target')) {
        if (!askConfirmation(sprintf("Do you really want to copy database from instance %s to instance %s",
            input()->getArgument('stage'),
            input()->getOption('db-target')), true)) {
            die('Process aborted');
        }

        $targetInstanceName = input()->getOption('db-target');
        if ($targetInstanceName == null) {
            throw new GracefulShutdownException(
                "You must set the target instance the database will be copied to as second parameter."
            );
        }
        if ($targetInstanceName == get('instance_live_name', 'live')) {
            throw new GracefulShutdownException(
                "FORBIDDEN: For security its forbidden to move database to live instance!"
            );
        }
        if ($targetInstanceName == get('instance_local_name', 'local')) {
            throw new GracefulShutdownException(
                "FORBIDDEN: For synchro local database use: \ndep db:pull live"
            );
        }
    } else {
        throw new GracefulShutdownException(
            "The target instance is not set as second parameter. Copy should be run as: dep db:copy source target"
        );
    }
    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter(output());
    $sourceInstance = get('target_stage');
    $dumpCode = md5(microtime(true) . rand(0, 10000));
    $dl = get('local/bin/deployer');
    if (get('current_stage') == get('target_stage')) {
        runLocally($dl . ' db:export --db-dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    } else {
        runLocally($dl . ' db:export ' . $sourceInstance . ' --db-dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
        runLocally($dl . ' db:download ' . $sourceInstance . ' --db-dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    }
    runLocally($dl . ' db:process --db-dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    if (get('current_stage') == $targetInstanceName) {
        runLocally($dl . ' db:import --db-dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
        runLocally($dl . ' db:rmdump --db-dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    } else {
        runLocally($dl . ' db:upload ' . $targetInstanceName . ' --db-dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
        runLocally($dl . ' db:import ' . $targetInstanceName . ' --db-dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
        runLocally($dl . ' db:rmdump ' . $targetInstanceName . ' --db-dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    }
})->desc('Synchronize database between instances');
