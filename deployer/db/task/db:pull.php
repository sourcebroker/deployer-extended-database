<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-pull
 */
task('db:pull', function () {
    if (null === input()->getArgument('stage')) {
        throw new \RuntimeException('The target instance is required for db:pull command.');
    }
    $sourceInstance = get('source_instance');
    $dumpCode = md5(microtime(true) . rand(0, 10000));

    $dl = get('local/bin/deployer');
    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter(output());
    runLocally($dl . ' db:export ' . $sourceInstance . ' --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    runLocally($dl . ' db:download ' . $sourceInstance . ' --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    runLocally($dl . ' db:process --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    runLocally($dl . ' db:import --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    runLocally($dl . ' db:compress --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    runLocally($dl . ' db:dumpclean' . $verbosity, 0);
})->desc('Synchronize database from target instance to current instance.');
