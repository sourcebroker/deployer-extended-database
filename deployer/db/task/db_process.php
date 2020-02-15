<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-process
 */
task('db:process', function () {
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode', true);
    if (empty(get('argument_stage'))) {
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
        $verbosity = (new ConsoleUtility())->getVerbosityAsParameter();
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        $options = (new ConsoleUtility())->getOptionsForCliUsage(['dumpcode' => $dumpCode]);
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:process ' . $options . ' ' . $verbosity);
    }
})->desc('Run commands that process mysql dump file directly');
