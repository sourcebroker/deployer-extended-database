<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-dumpclean
 */
task('db:dumpclean', function () {
    if (get('db_instance') == get('server')['name']) {
        $files = runLocally('ls -1t ' . get('db_current_server')->get('db_storage_path_current'))->toArray();
        $dumpsStorage = [];
        natsort($files);
        foreach (array_reverse($files) as $file) {
            $dumpcode = $instance = null;
            foreach (explode('#', $file) as $metaPart) {
                if (strpos($metaPart, 'server') === 0) {
                    $instance = explode(':', $metaPart)[1];
                }
                if (strpos($metaPart, 'dumpcode') === 0) {
                    $dumpcode = explode(':', $metaPart)[1];
                }
            }
            if (empty($instance) || empty($dumpcode)) {
                throw new \Exception(
                    'server: or dumpcode: can not be detected for file dump: "'
                    . (new FileUtility())->normalizeFolder(get('db_current_server')->get('db_storage_path_current')) . $file . '');
            }
            $dumpsStorage[$instance][$dumpcode] = $dumpcode;
        }
        $dbDumpCleanKeep = get('db_dumpclean_keep', 5);
        foreach ($dumpsStorage as $instance => $instanceDumps) {
            $instanceDumps = array_values($instanceDumps);
            if (is_array($dbDumpCleanKeep)) {
                $dbDumpCleanKeep = !empty($dbDumpCleanKeep[$instance]) ? $dbDumpCleanKeep[$instance] : !empty($dbDumpCleanKeep['*']) ? $dbDumpCleanKeep['*'] : 5;
            }
            if (count($instanceDumps) > $dbDumpCleanKeep) {
                for ($i = $dbDumpCleanKeep; $i < count($instanceDumps); $i++) {
                    writeln('Removing old dump with code: ' . $instanceDumps[$i]);
                    runLocally('cd ' . escapeshellarg(get('db_current_server')->get('db_storage_path_current'))
                        . ' && rm ' . '*dumpcode:' . $instanceDumps[$i] . '*');
                }
            }
        }
    } else {
        $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
        run('cd ' . $activePath .
            ' && {{bin/php}} {{bin/deployer}} db:dumpclean ' . (new ConsoleUtility())->getVerbosityAsParameter(output()));
    }
})->desc('Cleans the database dump storage.');
