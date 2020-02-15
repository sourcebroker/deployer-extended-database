<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-pull
 */
task('db:pull', function () {
    $sourceName = get('argument_stage');
    if (null !== $sourceName) {
        if (!get('db_allow_pull_live', false) && get('default_stage') === get('instance_live_name', 'live')) {
            throw new GracefulShutdownException(
                'FORBIDDEN: For security its forbidden to pull database to "'
                . get('instance_live_name', 'live') . '" instance! ' .
                ' Use "set(\'db_allow_push_live\', true);" to allow this. [Error code: 1488149981777]'
            );
        }
    } else {
        throw new GracefulShutdownException("The source instance is required for db:pull command. [Error code: 1488149981776]");
    }

    $dumpCode = md5(microtime(true) . rand(0, 10000));
    $dl = get('local/bin/deployer');
    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter();
    $options = (new ConsoleUtility())->getOptionsForCliUsage(['dumpcode' => $dumpCode]);
    runLocally($dl . ' db:export ' . $sourceName . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:download ' . $sourceName . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:process ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:import ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:compress ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:dumpclean' . $verbosity);
})->desc('Copy database from remote to local');
