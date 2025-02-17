<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\OptionUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-backup
 */
task('db:backup', function () {
    $consoleUtility = new ConsoleUtility();
    $optionUtility = new OptionUtility(input()->getOption('options'));
    $dumpCode = $optionUtility->getOption('dumpcode', false);
    if (!$dumpCode) {
        $optionUtility->setOption('dumpcode', $consoleUtility->getDumpCode());
    }

    $params = [
        'argument_host' => get('argument_host'),
        'verbose' => $consoleUtility->getVerbosityAsParameter(),
    ];

    if (get('is_argument_host_the_same_as_local_host')) {
        if (testLocally('cd {{deploy_path}} && [ -f .dep/latest_release ]')) {
            $latestRelease = (int)runLocally('cd {{deploy_path}} && cat .dep/latest_release || echo 0');
            $tags = $optionUtility->getOption('tags', false);
            if ($latestRelease > 0) {
                $tags[] = 'release';
                $tags[] = 'release_' . $latestRelease;
                $optionUtility->setOption('tags', $tags);
            }
        }
        $params['options'] = $optionUtility->getOptionsString();
        $dl = host(get('local_host'))->get('bin/php') . ' ' . get('local/bin/deployer');
        runLocally($dl . ' db:export ' . implode(' ', $params));
        runLocally($dl . ' db:compress  ' . implode(' ', $params));
        runLocally($dl . ' db:dumpclean ' . implode(' ', $params));
    } else {
        $params['options'] = $optionUtility->getOptionsString();
        run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:backup '
            . implode(' ', array_values($params)));
    }
})->desc('Do backup of database (export, compress, dumpclean)');
