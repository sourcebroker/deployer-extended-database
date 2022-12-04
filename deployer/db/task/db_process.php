<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-process
 */
task('db:process', function () {
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode', true);
    if (get('is_argument_host_the_same_as_local_host')) {
        $markersArray = [];
        $markersArray['{{databaseStorageAbsolutePath}}'] = get('db_storage_path_local');
        $markersArray['{{dumpcode}}'] = $dumpCode;
        if (get('db_process_commands', false) !== false) {
            foreach (get('db_process_commands') as $dbProcessCommand) {
                runLocally(str_replace(
                    array_keys($markersArray),
                    $markersArray,
                    $dbProcessCommand
                ));
            }
        }
    } else {
        $params = [
            get('argument_host'),
            (new ConsoleUtility())->getVerbosityAsParameter(),
            (new ConsoleUtility())->getOptionsForCliUsage(['dumpcode' => $dumpCode]),
        ];
        run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:process ' . implode(' ', $params));
    }
})->desc('Run commands that process mysql dump file directly');
