<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-push
 */
task('db:push', function () {
    $targetName = get('argument_host');
    if ($targetName === get('instance_live_name', 'live')) {
        if (!get('db_allow_push_live', true)) {
            throw new GracefulShutdownException(
                'FORBIDDEN: For security its forbidden to push database to top instance: "' . $targetName . '"!'
            );
        }
        if (!get('db_allow_push_live_force', false)) {
            writeln("<error>\n\n");
            writeln(sprintf(
                "You going to push database from instance: \"%s\" to top instance: \"%s\". ",
                get('local_host'),
                $targetName
            ));
            writeln("This can be destructive.\n\n");
            writeln("</error>");
            if (!askConfirmation('Do you really want to continue?', false)) {
                throw new GracefulShutdownException('Process aborted.');
            }
            if (!askConfirmation('Are you sure?', false)) {
                throw new GracefulShutdownException('Process aborted.');
            }
        }
    }

    $dumpCode = md5(microtime(true) . random_int(0, 10000));
    $dl = get('local/bin/deployer');
    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter();
    $options = (new ConsoleUtility())->getOptionsForCliUsage(['dumpcode' => $dumpCode]);
    $local = get('local_host');
    runLocally($dl . ' db:export ' . $local . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:upload ' . $targetName . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:process ' . $targetName . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:import ' . $targetName . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:rmdump ' . $targetName . ' ' . $options . ' ' . $verbosity);
})->desc('Copy database from local to remote');
