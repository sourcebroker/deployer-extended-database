<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-rmdump
 */
task('db:rmdump', function () {
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode', true);
    if (get('is_argument_host_the_same_as_local_host')) {
        $databaseUtility = new DatabaseUtility();
        $databaseUtility->removeDumpFiles(get('db_storage_path_local'), ['dumpcode' => $dumpCode]);
    } else {
        $params = [
            get('argument_host'),
            (new ConsoleUtility())->getVerbosityAsParameter(),
            input()->getOption('options') ? '--options=' . input()->getOption('options') : '',
        ];
        run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:rmdump ' . implode(' ', $params));
    }
})->desc('Remove all dumps with given dumpcode (compressed and uncompressed)');
