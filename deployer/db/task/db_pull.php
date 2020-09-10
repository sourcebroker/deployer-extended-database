<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-pull
 */
task('db:pull', function () {
    $sourceName = get('argument_stage');
    if (null === $sourceName) {
        throw new GracefulShutdownException("The source instance is required for db:pull command. [Error code: 1488149981776]");
    }

    if (get('default_stage') === get('instance_live_name', 'live')) {
        if (!get('db_allow_pull_live', true)) {
            throw new GracefulShutdownException(
                'FORBIDDEN: For security its forbidden to pull database to top instance: "' . get('default_stage') . '"!'
            );
        }
        if (!get('db_allow_pull_live_force', false)) {
            write("<error>\n\n");
            write(sprintf(
                "You going to pull database from instance: \"%s\" to top instance: \"%s\". ",
                $sourceName,
                get('default_stage')
            ));
            write("This can be destructive.\n\n");
            write("</error>");
            if (!askConfirmation('Do you really want to continue?', false)) {
                throw new GracefulShutdownException('Process aborted.');
            }
            if (!askConfirmation('Are you sure?', false)) {
                throw new GracefulShutdownException('Process aborted.');
            }
        }
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
