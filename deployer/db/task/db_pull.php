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
    $options = (new ConsoleUtility())->getOptionsForCliUsage(['dumpcode' => $dumpCode]);
    runLocally($dl . ' db:export ' . $sourceInstance . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:download ' . $sourceInstance . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:process ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:import ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:compress ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:dumpclean' . $verbosity);
})->desc('Synchronize database from target instance to current instance');
