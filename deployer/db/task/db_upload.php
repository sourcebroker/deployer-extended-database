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
    if (null === get('argument_stage')) {
        throw new GracefulShutdownException('The target instance is required for db:upload command.', 1500716535614);
    }
    $dumpCode = (new ConsoleUtility())->getOption('dumpcode', true);
    $rsyncUtility = new RsyncUtility();
    $fileUtility = new FileUtility();
    runLocally(sprintf(
        'rsync -rz --remove-source-files %s --include=%s --exclude=* %s %s',
        $rsyncUtility->getSshOptions(get('argument_stage')),
        escapeshellarg('*dumpcode=' . $dumpCode . '*'),
        escapeshellarg($fileUtility->normalizeFolder(get('db_storage_path_local'))),
        escapeshellarg($rsyncUtility->getHostWithDbStoragePath(get('argument_stage')))
    ));
})->desc('Upload the database dump for given dumpcode from local to remote database dumps storage');
