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
    $local = get('local_host');
    $dl = get('local/bin/deployer');
    $consoleUtility = new ConsoleUtility();
    $verbosity = $consoleUtility->getVerbosityAsParameter();
    $options = $consoleUtility->getOptionsForCliUsage(['dumpcode' => $consoleUtility->getDumpCode()]);
    output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:export ' . $local . ' ' . $options . ' ' . $verbosity)));
    output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:upload ' . $targetName . ' ' . $options . ' ' . $verbosity)));
    runLocally($dl . ' db:rmdump ' . $local . ' ' . $options . ' ' . $verbosity);
    output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:process ' . $targetName . ' ' . $options . ' ' . $verbosity)));
    // TODO: make backup before import
    $importOutput = runLocally($dl . ' db:import ' . $targetName . ' ' . $options . ' ' . $verbosity);
    output()->writeln($consoleUtility->formattingSubtaskTree(str_replace("task db:import\n", '', $importOutput)));
    runLocally($dl . ' db:rmdump ' . $targetName . ' ' . $options . ' ' . $verbosity);
})->desc('Push database from local to remote');
