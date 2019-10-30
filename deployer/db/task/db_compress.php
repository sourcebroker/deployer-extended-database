<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-compress
 */
task('db:compress', function () {
    $dumpCode = (new ConsoleUtility())->optionRequired('dumpcode', input());
    if (get('current_stage') == get('target_stage')) {
        $markersArray = [];
        $markersArray['{{databaseStorageAbsolutePath}}'] = get('db_storage_path_current');
        $markersArray['{{dumpcode}}'] = $dumpCode;
        if (get('db_compress_command', false) !== false) {
            foreach (get('db_compress_command') as $dbProcessCommand) {
                runLocally(str_replace(
                    array_keys($markersArray),
                    $markersArray,
                    $dbProcessCommand
                ), 0);
            }
        }
    } else {
        $verbosity = (new ConsoleUtility())->getVerbosityAsParameter(output());
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:compress --dumpcode=' . $dumpCode . ' ' . $verbosity);
    }
})->desc('Compress dumps with given dumpcode');
