<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\RsyncUtility;

task('db:upload', function () {
    if (null === input()->getArgument('stage')) {
        throw new \RuntimeException("The target instance is required for db:upload command.", 1500716535614);
    }
    if (input()->getOption('dumpcode')) {
        $dumpCode = input()->getOption('dumpcode');
    } else {
        throw new \InvalidArgumentException('No --dumpcode option set.', 1458937128560);
    }
    $rsyncUtility = new RsyncUtility();
    runLocally(sprintf(
        "rsync -rz --remove-source-files %s --include=%s --exclude=* %s %s",
        $rsyncUtility->getSshOptions(Task\Context::get()) ? '-e ' . escapeshellarg($rsyncUtility->getSshOptions(Task\Context::get())) : '',
        escapeshellarg('*dumpcode:' . $dumpCode . '*.sql'),
        escapeshellarg(get('db_current_server')->get('db_storage_path_current')),
        escapeshellarg($rsyncUtility->getServerWithDbStoragePath(Task\Context::get()))
    ), 0);
})->desc('Upload the latest database dump from target database dumps storage.');
