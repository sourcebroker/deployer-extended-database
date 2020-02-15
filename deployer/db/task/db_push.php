<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-push
 */
task('db:push', function () {
    $targetName = get('argument_stage');
    if (null !== $targetName) {
        if (!get('db_allow_push_live', false) && $targetName === get('instance_live_name', 'live')) {
            throw new GracefulShutdownException(
                'FORBIDDEN: For security its forbidden to push media to "'
                . get('instance_live_name', 'live') . '" instance!' .
                ' Use "set(\'db_allow_push_live\', true);" to allow this. [Error code: 1488149981778]'
            );
        }
    } else {
        throw new GracefulShutdownException("The target instance is required for media:push command. [Error code: 1488149981776]");
    }

    $dumpCode = md5(microtime(true) . rand(0, 10000));
    $dl = get('local/bin/deployer');
    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter();
    $options = (new ConsoleUtility())->getOptionsForCliUsage(['dumpcode' => $dumpCode]);
    runLocally($dl . ' db:export ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:upload ' . $targetName . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:process ' . $targetName . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:import ' . $targetName . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:rmdump ' . $targetName . ' ' . $options . ' ' . $verbosity);
})->desc('Copy database from local to remote');
