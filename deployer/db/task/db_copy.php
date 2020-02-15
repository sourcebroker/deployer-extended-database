<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-copy
 */
task('db:copy', function () {
    if (null === get('argument_stage')) {
        throw new GracefulShutdownException("The source instance is required for db:move command.");
    }
    $targetInstanceName = (new ConsoleUtility())->getOption('target');
    if ($targetInstanceName) {
        if (!askConfirmation(sprintf("Do you really want to copy database from instance %s to instance %s",
            get('argument_stage'), $targetInstanceName), true)) {
            throw new GracefulShutdownException(
                "Process aborted"
            );
        }
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
            "The target instance is not set as second parameter. You must set the target instance as '--options=target:[target-name]'"
        );
    }
    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter();
    $sourceInstance = get('argument_stage');
    $dl = get('local/bin/deployer');
    $options = (new ConsoleUtility())->getOptionsForCliUsage(['dumpcode' => md5(microtime(true) . rand(0, 10000))]);
    if (empty(get('argument_stage'))) {
        runLocally($dl . ' db:export ' . $options . ' ' . $verbosity);
    } else {
        runLocally($dl . ' db:export ' . $sourceInstance . ' ' . $options . ' ' . $verbosity);
        runLocally($dl . ' db:download ' . $sourceInstance . ' ' . $options . ' ' . $verbosity);
    }
    runLocally($dl . ' db:process ' . $options . ' ' . $verbosity);
    if (get('default_stage') == $targetInstanceName) {
        runLocally($dl . ' db:import ' . $options . ' ' . $verbosity);
        runLocally($dl . ' db:rmdump ' . $options . ' ' . $verbosity);
    } else {
        runLocally($dl . ' db:upload ' . $targetInstanceName . ' ' . $options . ' ' . $verbosity);
        runLocally($dl . ' db:import ' . $targetInstanceName . ' ' . $options . ' ' . $verbosity);
        runLocally($dl . ' db:rmdump ' . $targetInstanceName . ' ' . $options . ' ' . $verbosity);
    }
})->desc('Synchronize database between instances');
