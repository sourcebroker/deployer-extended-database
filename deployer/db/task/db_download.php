<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\RsyncUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-download
 */
task('db:download', function () {
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode', true);
    $rsyncUtility = new RsyncUtility();
    $fileUtility = new FileUtility();
    runLocally(sprintf(
        'rsync -rz --remove-source-files %s --include=%s --exclude=* %s %s',
        $rsyncUtility->getSshOptions(get('argument_host')),
        escapeshellarg('*dumpcode=' . $dumpCode . '*'),
        escapeshellarg($rsyncUtility->getHostWithDbStoragePath(get('argument_host'))),
        escapeshellarg($fileUtility->normalizeFolder(get('db_storage_path_local')))
    ));
})->desc('Download the database dumps with given dumpcode from remote to local database dumps storage');
