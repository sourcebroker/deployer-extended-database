<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;

task('db:upload', function () {
    if (null === input()->getArgument('stage')) {
        throw new \RuntimeException("The target instance is required for db:upload command.");
    }
    if (input()->getOption('dumpcode')) {
        $dumpCode = input()->getOption('dumpcode');
    } else {
        throw new \InvalidArgumentException('No --dumpcode option set. [Error code: 1458937128560]');
    }
    $targetInstance = Task\Context::get()->getServer()->getConfiguration();
    $targetInstanceDatabaseStoragePath = FileUtility::normalizeFolder(get('db_storage_path'));

    $port = $targetInstance->getPort() ? ' -p' . $targetInstance->getPort() : '';
    $identityFile = $targetInstance->getPrivateKey() ? ' -i ' . $targetInstance->getPrivateKey() : '';
    if ($port !== '' || $identityFile !== '') {
        $sshOptions = '-e ' . escapeshellarg('ssh ' . $port . $identityFile);
    } else {
        $sshOptions = '';
    }
    runLocally(sprintf(
        "rsync -rz --remove-source-files %s --include=*dumpcode:%s*.sql --exclude=* '%s/' '%s%s:%s/'",
        $sshOptions,
        $dumpCode,
        get('db_current_server')->get('db_storage_path_current'),
        $targetInstance->getUser() ? $targetInstance->getUser() . '@' : '',
        $targetInstance->getHost(),
        $targetInstanceDatabaseStoragePath
    ), 0);
})->desc('Upload the latest database dump from target database dumps storage.');
