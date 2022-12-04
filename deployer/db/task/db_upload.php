<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\RsyncUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-upload
 */
task('db:upload', function () {
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode', true);
    $rsyncUtility = new RsyncUtility();
    $fileUtility = new FileUtility();
    runLocally(sprintf(
        'rsync -rz --remove-source-files %s --include=%s --exclude=* %s %s',
        $rsyncUtility->getSshOptions(get('argument_host')),
        escapeshellarg('*dumpcode=' . $dumpCode . '*'),
        escapeshellarg($fileUtility->normalizeFolder(get('db_storage_path_local'))),
        escapeshellarg($rsyncUtility->getHostWithDbStoragePath(get('argument_host')))
    ));
})->desc('Upload the database dump for given dumpcode from local to remote database dumps storage');
