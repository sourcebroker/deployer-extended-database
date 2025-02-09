<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-pull
 */
task('db:pull', function () {
    $sourceName = get('argument_host');
    if (null === $sourceName) {
        throw new GracefulShutdownException("The source instance is required for db:pull command. [Error code: 1488149981776]");
    }

    if (get('local_host') === get('instance_live_name', 'live')) {
        if (!get('db_allow_pull_live', true)) {
            throw new GracefulShutdownException(
                'FORBIDDEN: For security its forbidden to pull database to top instance: "' . get('local_host') . '"!'
            );
        }
        if (!get('db_allow_pull_live_force', false)) {
            writeln("<error>\n\n");
            writeln(sprintf(
                "You going to pull database from instance: \"%s\" to top instance: \"%s\". ",
                $sourceName,
                get('local_host')
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
    output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:export ' . $sourceName . ' ' . $options . ' ' . $verbosity)));
    output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:download ' . $sourceName . ' ' . $options . ' ' . $verbosity)));
    runLocally($dl . ' db:rmdump ' . $sourceName . ' ' . $options . ' ' . $verbosity);
    output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:process ' . $local . ' ' . $options . ' ' . $verbosity)));
    // TODO: make backup before import, compress and rotate
    output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:import ' . $local . ' ' . $options . ' ' . $verbosity)));
    runLocally($dl . ' db:rmdump ' . $local . ' ' . $options . ' ' . $verbosity);
})->desc('Pull database from remote to local');
