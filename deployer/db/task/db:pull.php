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
    $sourceInstance = get('server')['name'];
    $dumpCode = md5(microtime(true) . rand(0, 10000));

    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter(output());
    runLocally('{{local/bin/deployer}} db:export ' . $sourceInstance . ' --dumpcode=' . $dumpCode
        . ' ' . $verbosity, 0);
    runLocally('{{local/bin/deployer}} db:download ' . $sourceInstance . ' --dumpcode=' . $dumpCode
        . ' ' . $verbosity, 0);
    runLocally('{{local/bin/deployer}} db:process --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
    runLocally('{{local/bin/deployer}} db:import --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
})->desc('Synchronize database from target instance to current instance.');
