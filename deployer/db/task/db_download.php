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
    if (null === input()->getArgument('stage')) {
        throw new GracefulShutdownException('The target instance is required for db:download command.', 1488143750580);
    }
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode', true);
    $rsyncUtility = new RsyncUtility();
    $fileUtility = new FileUtility();
    runLocally(sprintf(
        'rsync -rz --remove-source-files %s --include=%s --exclude=* %s %s',
        $rsyncUtility->getSshOptions(get('target_stage')),
        escapeshellarg('*dumpcode=' . $dumpCode . '*'),
        escapeshellarg($rsyncUtility->getHostWithDbStoragePath(get('target_stage'))),
        escapeshellarg($fileUtility->normalizeFolder(get('db_storage_path_current')))
    ));
})->desc('Download the database dumps with given dumpcode from target to current database dumps storage');
