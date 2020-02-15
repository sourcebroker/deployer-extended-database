<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-rmdump
 */
task('db:rmdump', function () {
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode', true);
    if (empty(get('argument_stage'))) {
        runLocally('cd ' . get('db_storage_path_local') .
            ' && rm -f *dumpcode=' . $dumpCode . '*');
    } else {
        $verbosity = (new ConsoleUtility())->getVerbosityAsParameter();
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        $options = (new ConsoleUtility())->getOptionsForCliUsage(['dumpcode' => $dumpCode]);
        run('cd ' . $activePath . ' && {{bin/php}} {{bin/deployer}} db:rmdump ' . $options . ' ' . $verbosity);
    }
})->desc('Remove all dumps with given dumpcode (compressed and uncompressed)');
