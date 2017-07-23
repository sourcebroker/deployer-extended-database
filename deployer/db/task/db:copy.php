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
    $sourceInstance = get('server')['name'];
    $dumpCode = md5(microtime(true) . rand(0, 10000));
    if (get('db_instance') == get('server')['name']) {
        runLocally('{{local/bin/deployer}} db:export --dumpcode=' . $dumpCode . ' ' . $verbosity,
            0);
    } else {
        runLocally('{{local/bin/deployer}} db:export ' . $sourceInstance . ' --dumpcode=' . $dumpCode . ' ' . $verbosity);
        runLocally('{{local/bin/deployer}} db:download ' . $sourceInstance . ' --dumpcode=' . $dumpCode . ' ' . $verbosity,
            0);
    }
    runLocally('{{local/bin/deployer}} db:process --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    if (get('db_instance') == $targetInstanceName) {
        runLocally('{{local/bin/deployer}} db:import --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    } else {
        runLocally('{{local/bin/deployer}} db:upload ' . $targetInstanceName . ' --dumpcode=' . $dumpCode . ' ' . $verbosity,
            0);
        runLocally('{{local/bin/deployer}} db:import ' . $targetInstanceName . ' --dumpcode=' . $dumpCode . ' ' . $verbosity,
            0);
    }
})->desc('Synchronize database between instances.');
