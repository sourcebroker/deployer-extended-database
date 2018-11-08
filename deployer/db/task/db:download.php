<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\RsyncUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-download
 */
task('db:download', function () {
    if (null === input()->getArgument('stage')) {
        throw new \RuntimeException('The source instance is required for db:download command.', 1488143750580);
    }
    $dumpCode = (new ConsoleUtility())->optionRequired('dumpcode', input());
    $rsyncUtility = new RsyncUtility();
    $fileUtility = new FileUtility();
    runLocally(sprintf(
        'rsync -rz --remove-source-files %s --include=%s --exclude=* %s %s',
        $rsyncUtility->getSshOptions(Task\Context::get()) ? '-e '
            . escapeshellarg($rsyncUtility->getSshOptions(Task\Context::get())) : '',
        escapeshellarg('*dumpcode=' . $dumpCode . '*'),
        escapeshellarg($rsyncUtility->getHostWithDbStoragePath(Task\Context::get())),
        escapeshellarg($fileUtility->normalizeFolder(get('db_current_server')->get('db_storage_path_current')))
    ), 0);
})->desc('Download the database dumps with given dumpcode from target to current database dumps storage.');
