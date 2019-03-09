<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-copy
 */
task('db:copy', function () {
    if (null === input()->getArgument('stage')) {
        throw new \RuntimeException("The source instance is required for db:move command.");
    }
    if (input()->getArgument('targetStage')) {
        if (!askConfirmation(sprintf("Do you really want to copy database from instance %s to instance %s",
          input()->getArgument('stage'),
          input()->getArgument('targetStage')), true)) {
            die('Process aborted');
        }

        $targetInstanceName = input()->getArgument('targetStage');
        if ($targetInstanceName == null) {
            throw new \RuntimeException(
              "You must set the target instance the database will be copied to as second parameter."
            );
        }
        // TODO - instance name hardcoded
        if ($targetInstanceName == 'live') {
            throw new \RuntimeException(
              "FORBIDDEN: For security its forbidden to move database to live instance!"
            );
        }
        // TODO - instance name hardcoded
        if ($targetInstanceName == 'local') {
            throw new \RuntimeException(
              "FORBIDDEN: For synchro local database use: \ndep db:pull live"
            );
        }
    } else {
        throw new \RuntimeException(
          "The target instance is not set as second parameter. Move should be run as: dep db:move source target"
        );
    }
    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter(output());
    $sourceInstance = get('target_stage');
    $dumpCode = md5(microtime(true) . rand(0, 10000));
    $dl = get('local/bin/deployer');
    if (get('current_stage') == get('target_stage')) {
        runLocally($dl . ' db:export --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    } else {
        runLocally($dl . ' db:export ' . $sourceInstance . ' --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
        runLocally($dl . ' db:download ' . $sourceInstance . ' --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    }
    runLocally($dl . ' db:process --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    if (get('current_stage') == $targetInstanceName) {
        runLocally($dl . ' db:import --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
        runLocally($dl . ' db:rmdump --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    } else {
        runLocally($dl . ' db:upload ' . $targetInstanceName . ' --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
        runLocally($dl . ' db:import ' . $targetInstanceName . ' --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
        runLocally($dl . ' db:rmdump ' . $targetInstanceName . ' --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    }
})->desc('Synchronize database between instances.');
