<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-import-last
 */
task('db:import:last', function () {
    $consoleUtility = new ConsoleUtility();
    $databaseUtility = new DatabaseUtility();
    $fileUtility = new FileUtility();

    if (get('is_argument_host_the_same_as_local_host')) {
        $lastDumpFilename = $databaseUtility->getLastDumpFilename($fileUtility->normalizeFolder(get('db_storage_path_local')));

        if ($lastDumpFilename === null) {
            throw new GracefulShutdownException(
                "No dump found in " . get('db_storage_path_local')
            );
        }

        $dumpCode = $fileUtility->getDumpFilenamePart($lastDumpFilename, 'dumpcode');
        $dateTime = $fileUtility->getDumpFilenamePart($lastDumpFilename, 'dateTime');
        $server = $fileUtility->getDumpFilenamePart($lastDumpFilename, 'server');

        output()->writeln($consoleUtility->formattingTaskOutputHeader('Last dump found:'));
        output()->writeln($consoleUtility->formattingTaskOutputContent('- dumpcode: ' . $dumpCode));
        output()->writeln($consoleUtility->formattingTaskOutputContent('- date: ' . $dateTime->format('Y-m-d H:i:s')));
        output()->writeln($consoleUtility->formattingTaskOutputContent('- from host: ' . $server));
        $params = [
            get('local_host'),
            $consoleUtility->getVerbosityAsParameter(),
            '--options=dumpcode:' . $dumpCode,
        ];

        if (!askConfirmation("Do you really want to import above dump?", true)) {
            throw new GracefulShutdownException("Process aborted");
        }
        $pathInfo = pathinfo($lastDumpFilename);
        if (isset($pathInfo['extension']) && $pathInfo['extension'] === 'gz') {
            runLocally('cd {{deploy_path}} && {{local/bin/deployer}} db:decompress ' . implode(' ', $params));
        }
        runLocally('cd {{deploy_path}} && {{local/bin/deployer}} db:import ' . implode(' ', $params));
    } else {
        throw new GracefulShutdownException(
            "This task can be run only on local host. Your local host name is: '" . get('local_host') . "'."
        );
    }
})->desc('Import last dump from local database storage.');
