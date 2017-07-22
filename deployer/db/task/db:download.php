<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;

task('db:download', function () {
    $fileUtility = new FileUtility();
    if (null === input()->getArgument('stage')) {
        throw new \RuntimeException('The target instance is required for db:download command.', 1488143750580);
    }
    $dumpCode = (new ConsoleUtility())->optionRequired('dumpcode', input());
    $currentInstanceDatabaseStoragePath = $fileUtility->normalizeFolder(get('db_current_server')->get('db_storage_path_current'));
    $rsyncUtility = new \SourceBroker\DeployerExtendedDatabase\Utility\RsyncUtility();
    runLocally(sprintf(
        "rsync -rz --remove-source-files %s --include=*dumpcode:%s*.sql --exclude=* %s %s",
        $rsyncUtility->getSshOptions(Task\Context::get()) ? '-e ' . escapeshellarg($rsyncUtility->getSshOptions(Task\Context::get())) : '',
        $dumpCode,
        escapeshellarg($rsyncUtility->getServerWithDbStoragePath(Task\Context::get())),
        escapeshellarg($currentInstanceDatabaseStoragePath)
    ), 0);
})->desc('Download the database dumps with dumpcode from target database dumps storage.');
