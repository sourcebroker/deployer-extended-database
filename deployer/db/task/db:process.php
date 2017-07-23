<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-process
 */
task('db:process', function () {
    if (null !== input()->getArgument('stage')) {
        throw new \RuntimeException('You can not set target instance for db:process command. 
        It can only run on current instance.', 1500721553545);
    }
    $consoleUtility = new ConsoleUtility();
    $consoleUtility->optionRequired('dumpcode', input());
    $currentInstanceDatabaseStoragePath = get('db_current_server')->get('db_storage_path_current');

    // remove "DEFINER" from the dump files to avoid problems with DEFINER views permissions
    // use hack for set multiple OS support (OSX/Linux) @see http://stackoverflow.com/a/38595160/1588346
    runLocally('sed --version >/dev/null 2>&1 && sed -i -- "s/DEFINER=[^*]*\*/\*/g" ' . $currentInstanceDatabaseStoragePath . '/*dumpcode:' . $dumpCode . '*.sql || sed -i \'\' \'s/DEFINER=[^*]*\*/\*/g\' ' . $currentInstanceDatabaseStoragePath . '/*dumpcode:' . $dumpCode . '*.sql');
})->desc('Run DEFINER replace on local databases defined by "dumpcode".');
