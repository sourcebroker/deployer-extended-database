<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-dumpclean
 */
task('db:dumpclean', function () {
    if (get('is_argument_host_the_same_as_local_host')) {
        $fileUtility = new FileUtility();
        $databaseUtility = new DatabaseUtility();
        $dumpFiles = (new DatabaseUtility())->getDumpFiles(
            get('db_storage_path_local'), [], ['sql', 'gz'], ['dateTime' => 'desc']
        );

        $dumpsStorage = [];
        foreach ($dumpFiles as $file) {
            $dumpcode = $fileUtility->getDumpFilenamePart($file, 'dumpcode');
            $server = $fileUtility->getDumpFilenamePart($file, 'server');

            if (empty($server) || empty($dumpcode)) {
                writeln('Note: "server" or "dumpcode" can not be detected for file dump: "'
                    . get('db_storage_path_local')
                    . $file);
                writeln('Seems like this file was not created by deployer-extended-database or was created by previous version of deployer-extended-database. Please remove this file manually to get rid of this notice.');
                writeln('');
                continue;
            }
            $dumpsStorage[$server][$dumpcode] = $dumpcode;
        }

        $dbDumpCleanKeep = get('db_dumpclean_keep', 5);
        foreach ($dumpsStorage as $server => $serverDumps) {
            $serverDumps = array_values($serverDumps);

            if (is_array($dbDumpCleanKeep)) {
                if (!empty($dbDumpCleanKeep[$server])) {
                    $keepCount = $dbDumpCleanKeep[$server];
                } elseif (!empty($dbDumpCleanKeep['*'])) {
                    $keepCount = $dbDumpCleanKeep['*'];
                } else {
                    $keepCount = 5;
                }
            } else {
                $keepCount = $dbDumpCleanKeep;
            }

            if (count($serverDumps) > $keepCount) {
                $serverDumpsCount = count($serverDumps);
                for ($i = $keepCount; $i < $serverDumpsCount; $i++) {
                    writeln('Removing old dump with code: ' . $serverDumps[$i], OutputInterface::VERBOSITY_VERBOSE);
                    $databaseUtility->removeDumpFiles(get('db_storage_path_local'), ['dumpcode' => $serverDumps[$i]]);
                }
            }
        }
    } else {
        run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:dumpclean '
            . get('argument_host') . ' ' . (new ConsoleUtility())->getVerbosityAsParameter());
    }
})->desc('Cleans the database dump storage');
