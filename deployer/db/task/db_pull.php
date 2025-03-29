<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\OptionUtility;

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
    $dl = get('local/bin/php') . ' ' . get('local/bin/deployer');
    $consoleUtility = new ConsoleUtility();
    $verbosity = $consoleUtility->getVerbosityAsParameter();
    $optionUtility = new OptionUtility(input()->getOption('options'));
    $optionUtility->setOption('tags', ['pull']);

    $fromStorage = $optionUtility->getOption('fromLocalStorage');
    if ($fromStorage) {
        $databaseUtility = new DatabaseUtility();
        $lastDumpFilename = $databaseUtility->getLastDumpFile(
            get('db_storage_path_local'), ['server' => get('argument_host')]
        );
        if ($lastDumpFilename === null) {
            output()->writeln($consoleUtility->formattingTaskOutputHeader('No database dumps found for `' . get('argument_host') . '` in local database storage.'));
            return;
        }
        $fileUtility = new FileUtility();
        $optionUtility->setOption('dumpcode', $fileUtility->getDumpFilenamePart($lastDumpFilename, 'dumpcode'));
        $optionUtility->removeOption('fromLocalStorage');
        $options = $optionUtility->getOptionsString();

        $dumpCode = $fileUtility->getDumpFilenamePart($lastDumpFilename, 'dumpcode');
        $dateTime = $fileUtility->getDumpFilenamePart($lastDumpFilename, 'dateTime');
        $server = $fileUtility->getDumpFilenamePart($lastDumpFilename, 'server');

        output()->writeln($consoleUtility->formattingTaskOutputHeader('Last dump found:'));
        output()->writeln($consoleUtility->formattingTaskOutputContent('- dumpcode: ' . $dumpCode));
        output()->writeln($consoleUtility->formattingTaskOutputContent('- date: ' . $dateTime->format('Y-m-d H:i:s')));
        output()->writeln($consoleUtility->formattingTaskOutputContent('- from host: ' . $server));

        runLocally($dl . ' db:decompress ' . $local . ' ' . $options . ' ' . $verbosity);
    } else {
        $optionUtility->setOption('dumpcode', $consoleUtility->getDumpCode());
        $options = $optionUtility->getOptionsString();

        output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:export ' . $sourceName . ' ' . $options . ' ' . $verbosity)));
        output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:download ' . $sourceName . ' ' . $options . ' ' . $verbosity)));
        runLocally($dl . ' db:rmdump ' . $sourceName . ' ' . $options . ' ' . $verbosity);
        runLocally($dl . ' db:process ' . $local . ' ' . $options . ' ' . $verbosity);

        // Make backup of local database before import. No backup if $from Storage=true as it expect to be fast repeatable last downloaded db import.
        $backupOptions = new OptionUtility();
        $backupOptions->setOption('dumpcode', $consoleUtility->getDumpCode());
        $backupOptions->setOption('tags', ['pull', 'import_backup']);
        runLocally($dl . ' db:backup ' . $local . ' ' . $backupOptions->getOptionsString() . ' ' . $verbosity);
    }

    output()->writeln($consoleUtility->formattingSubtaskTree(runLocally($dl . ' db:import ' . $local . ' ' . $options . ' ' . $verbosity)));
    runLocally($dl . ' db:compress ' . $local . ' ' . $options . ' ' . $verbosity);
    runLocally($dl . ' db:dumpclean ' . $local . ' ' . $verbosity);
})->desc('Pull database from remote to local');
