<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-backup
 */
task('db:backup', function () {
    $verbosity = (new ConsoleUtility())->getVerbosityAsParameter(output());
    input()->getOption('dumpcode');
    if (!empty(input()->getOption('dumpcode'))) {
        $dumpCode = input()->getOption('dumpcode');
    } else {
        if (get('current_stage') == get('target_stage')) {
            $list = [];
            if (testLocally('[ -e {{deploy_path}}/releases ]')) {
                $list = runLocally('cd releases && ls -t -1 -d */')->toArray();
                $list = array_map(function ($release) {
                    return basename(rtrim(trim($release), '/'));
                }, $list);
            }
        } else {
            $list = get('releases_list');
        }
        $list = array_filter($list, function ($release) {
            return preg_match('/^[\d\.]+$/', $release);
        });
        $dumpCodeRealese = '';
        if (count($list) > 0) {
            $currentRelease = (int)max($list);
            $dumpCodeRealese = '_for_release_' . $currentRelease;
        }
        $dumpCode = 'backup' . $dumpCodeRealese . '_' . md5(microtime(true) . rand(0, 10000));
    }
    if (get('current_stage') == get('target_stage')) {
        runLocally('{{local/bin/deployer}} db:export --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
        runLocally('{{local/bin/deployer}} db:compress --dumpcode=' . $dumpCode . ' ' . $verbosity, 0);
        runLocally('{{local/bin/deployer}} db:dumpclean' . $verbosity, 0);
    } else {
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath .
            ' && {{bin/php}} {{bin/deployer}} db:export --dumpcode=' . $dumpCode . ' ' . $verbosity);
        run('cd ' . $activePath .
            ' && {{bin/php}} {{bin/deployer}} db:compress --dumpcode=' . $dumpCode . ' ' . $verbosity);
        run('cd ' . $activePath .
            ' && {{bin/php}} {{bin/deployer}} db:dumpclean' . $verbosity);
    }
})->desc('Do backup of database (export and compress).');
