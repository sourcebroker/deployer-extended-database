<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\RsyncUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-upload
 */
task('db:upload', function () {
    if (null === input()->getArgument('stage')) {
        throw new \RuntimeException('The target instance is required for db:upload command.', 1500716535614);
    }
    $dumpCode = (new ConsoleUtility())->optionRequired('dumpcode', input());
    $rsyncUtility = new RsyncUtility();
    $fileUtility = new FileUtility();
    runLocally(sprintf(
        'rsync -rz --remove-source-files %s --include=%s --exclude=* %s %s',
        $rsyncUtility->getSshOptions(Task\Context::get()) ? '-e '
            . escapeshellarg($rsyncUtility->getSshOptions(Task\Context::get())) : '',
        escapeshellarg('*dumpcode:' . $dumpCode . '*.sql'),
        escapeshellarg($fileUtility->normalizeFolder(get('db_current_server')->get('db_storage_path_current'))),
        escapeshellarg($rsyncUtility->getHostWithDbStoragePath(Task\Context::get()))
    ), 0);
})->desc('Upload the latest database dump from target database dumps storage.');
