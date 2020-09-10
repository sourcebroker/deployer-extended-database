<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-push
 */
task('db:push', function () {
    $targetName = get('argument_stage');
    if (null === $targetName) {
        throw new GracefulShutdownException("The target instance is required for media:push command. [Error code: 1488149981776]");
    }

    if ($targetName === get('instance_live_name', 'live')) {
        if (!get('db_allow_push_live', true)) {
            throw new GracefulShutdownException(
                'FORBIDDEN: For security its forbidden to push database to top instance: "' . $targetName . '"!'
            );
        }
        if (!get('db_allow_push_live_force', false)) {
            write("<error>\n\n");
            write(sprintf(
                "You going to push database from instance: \"%s\" to top instance: \"%s\". ",
                get('default_stage'),
                $targetName
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
    runLocally($dl . ' db:export ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:upload ' . $targetName . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:process ' . $targetName . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:import ' . $targetName . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:rmdump ' . $targetName . ' ' . $options . ' ' . $verbosity);
})->desc('Copy database from local to remote');
