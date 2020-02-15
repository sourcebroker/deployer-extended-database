<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\RsyncUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-download
 */
task('db:download', function () {
    if (null === get('argument_stage')) {
        throw new GracefulShutdownException('The target instance is required for db:download command.', 1488143750580);
    }
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode', true);
    $rsyncUtility = new RsyncUtility();
    $fileUtility = new FileUtility();
    runLocally(sprintf(
        'rsync -rz --remove-source-files %s --include=%s --exclude=* %s %s',
        $rsyncUtility->getSshOptions(get('argument_stage')),
        escapeshellarg('*dumpcode=' . $dumpCode . '*'),
        escapeshellarg($rsyncUtility->getHostWithDbStoragePath(get('argument_stage'))),
        escapeshellarg($fileUtility->normalizeFolder(get('db_storage_path_local')))
    ));
})->desc('Download the database dumps with given dumpcode from remote to local database dumps storage');
