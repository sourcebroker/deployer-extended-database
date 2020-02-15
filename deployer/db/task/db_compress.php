<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-compress
 */
task('db:compress', function () {
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode', true);
    if (empty(get('argument_stage'))) {
        $markersArray = [];
        $markersArray['{{databaseStorageAbsolutePath}}'] = get('db_storage_path_local');
        $markersArray['{{dumpcode}}'] = $dumpCode;
        if (get('db_compress_command', false) !== false) {
            foreach (get('db_compress_command') as $dbProcessCommand) {
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
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:compress ' . $options . ' ' . $verbosity);
    }
})->desc('Compress dumps with given dumpcode');
