<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-dumpclean
 */
task('db:dumpclean', function () {
    if (get('is_argument_host_the_same_as_local_host')) {
        $files = explode("\n", runLocally('ls -1t ' . get('db_storage_path_local')));
        $dumpsStorage = [];
        natsort($files);
        foreach (array_reverse($files) as $file) {
            $dumpcode = $instance = null;
            foreach (explode('#', $file) as $metaPart) {
                if (str_starts_with($metaPart, 'server')) {
                    $instanceParts = explode('=', $metaPart);
                    $instance = $instanceParts[1] ?? null;
                }
                if (str_starts_with($metaPart, 'dumpcode')) {
                    $dumpcodeParts = explode('=', $metaPart);
                    $dumpcode = $dumpcodeParts[1] ?? null;
                }
            }
            if (empty($instance) || empty($dumpcode)) {
                writeln('Note: "server" or "dumpcode" can not be detected for file dump: "'
                    . (new FileUtility())->normalizeFolder(get('db_storage_path_local'))
                    . $file);
                writeln('Seems like this file was not created by deployer-extended-database or was created by previous version of deployer-extended-database. Please remove this file manually to get rid of this notice.');
                writeln('');
                continue;
            }
            $dumpsStorage[$instance][$dumpcode] = $dumpcode;
        }
        $dbDumpCleanKeep = get('db_dumpclean_keep', 5);
        foreach ($dumpsStorage as $instance => $instanceDumps) {
            $instanceDumps = array_values($instanceDumps);
            if (is_array($dbDumpCleanKeep)) {
                $dbDumpCleanKeep = !empty($dbDumpCleanKeep[$instance]) ? $dbDumpCleanKeep[$instance] : (!empty($dbDumpCleanKeep['*']) ? $dbDumpCleanKeep['*'] : 5);
            }
            if (count($instanceDumps) > $dbDumpCleanKeep) {
                $instanceDumpsCount = count($instanceDumps);
                for ($i = $dbDumpCleanKeep; $i < $instanceDumpsCount; $i++) {
                    writeln('Removing old dump with code: ' . $instanceDumps[$i], OutputInterface::VERBOSITY_VERBOSE);
                    runLocally('cd ' . escapeshellarg(get('db_storage_path_local'))
                        . ' && rm ' . '*dumpcode=' . $instanceDumps[$i] . '*');
                }
            }
        }
    } else {
        run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:dumpclean '
            . get('argument_host') . ' ' . (new ConsoleUtility())->getVerbosityAsParameter());
    }
})->desc('Cleans the database dump storage');
