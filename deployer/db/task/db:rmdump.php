<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-rmdump
 */
task('db:rmdump', function () {
    $dumpCode = (new ConsoleUtility())->optionRequired('dumpcode', input());
    if (get('current_stage') == get('target_stage')) {
        runLocally('cd ' . get('db_storage_path_current') .
            ' && rm -f *dumpcode=' . $dumpCode . '*', 0);
    } else {
        $verbosity = (new ConsoleUtility())->getVerbosityAsParameter(output());
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:rmdump --dumpcode=' . $dumpCode . ' ' . $verbosity);
    }
})->desc('Remove all dumps with given dumpcode. (compressed and uncompressed)');
