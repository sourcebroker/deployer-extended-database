<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-copy
 */
task('db:copy', function () {
    $consoleUtility = new ConsoleUtility();
    $targetInstanceName = $consoleUtility->getOption('target');
    if (null === $targetInstanceName) {
        throw new GracefulShutdownException(
            "The target instance is not set in options. You must set the target instance as '--options=target:[target-name]'"
        );
    }

    $doNotAskAgainForLive = false;
    if ($targetInstanceName === get('instance_live_name', 'live')) {
        if (!get('db_allow_copy_live', true)) {
            throw new GracefulShutdownException(
                'FORBIDDEN: For security its forbidden to copy database to top instance: "' . $targetInstanceName . '"!'
            );
        }
        if (!get('db_allow_copy_live_force', false)) {
            $doNotAskAgainForLive = true;
            writeln("<error>\n\n");
            writeln(sprintf(
                "You going to copy database form instance: \"%s\" to top instance: \"%s\". ",
                get('argument_host'),
                $targetInstanceName
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

    if ($targetInstanceName === get('instance_local_name', 'local')) {
        throw new GracefulShutdownException(
            "FORBIDDEN: For synchro local database use: \ndep db:pull live"
        );
    }

    if (!$doNotAskAgainForLive && !askConfirmation(sprintf(
            "Do you really want to copy database from instance %s to instance %s",
            get('argument_host'),
            $targetInstanceName
        ), true)) {
        throw new GracefulShutdownException(
            "Process aborted"
        );
    }

    $verbosity = $consoleUtility->getVerbosityAsParameter();
    $sourceInstance = get('argument_host');
    $dl = get('local/bin/deployer');
    $options = $consoleUtility->getOptionsForCliUsage(['dumpcode' => $consoleUtility->getDumpCode()]);
    $local = get('local_host');
    if (get('is_argument_host_the_same_as_local_host')) {
        output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:export ' . $local . ' ' . $options . ' ' . $verbosity)));
    } else {
        output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:export ' . $sourceInstance . ' ' . $options . ' ' . $verbosity)));
        output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:download ' . $sourceInstance . ' ' . $options . ' ' . $verbosity)));
        runLocally($dl . ' db:rmdump ' . $sourceInstance . ' ' . $options . ' ' . $verbosity);
    }
    output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:upload ' . $targetInstanceName . ' ' . $options . ' ' . $verbosity)));
    runLocally($dl . ' db:rmdump ' . $local . ' ' . $options . ' ' . $verbosity);
    output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:process ' . $targetInstanceName . ' ' . $options . ' ' . $verbosity)));

    // TODO: make backup before import, compress and rotate
    $importOutput = runLocally($dl . ' db:import ' . $targetInstanceName . ' ' . $options . ' ' . $verbosity);
    output()->writeln($consoleUtility->formattingSubtaskTree(str_replace("task db:import\n", '', $importOutput)));
    runLocally($dl . ' db:rmdump ' . $targetInstanceName . ' ' . $options . ' ' . $verbosity);
})->desc('Synchronize database between instances');
