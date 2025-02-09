<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-backup
 */
task('db:backup', function () {
    if (get('is_argument_host_the_same_as_local_host')) {
        $latest = runLocally('cd {{deploy_path}} && cat .dep/latest_release || echo 0');
    } else {
        $latest = run('cd {{deploy_path}} && cat .dep/latest_release || echo 0');
    }

    $consoleUtility = new ConsoleUtility();
    $dumpCodeRelease = (int)$latest ? '_for_release_' . $latest : '';
    $dumpCode = 'backup' . $dumpCodeRelease . '_' . $consoleUtility->getDumpCode();
    $params = [
        get('argument_host'),
        $consoleUtility->getOptionsForCliUsage(['dumpcode' => $dumpCode]),
        $consoleUtility->getVerbosityAsParameter(),
    ];

    if (get('is_argument_host_the_same_as_local_host')) {
        runLocally('{{local/bin/deployer}} db:export ' . implode(' ', $params));
        runLocally('{{local/bin/deployer}} db:compress  ' . implode(' ', $params));
        runLocally('{{local/bin/deployer}} db:dumpclean ' . implode(' ', $params));
    } else {
        run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:export ' . implode(' ', $params));
        run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:compress ' . implode(' ', $params));
        run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:dumpclean ' . implode(' ', $params));
    }
})->desc('Do backup of database (export and compress)');
