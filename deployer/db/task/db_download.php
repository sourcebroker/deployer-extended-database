<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\OptionUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\RsyncUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-download
 */
task('db:download', function () {
    $rsyncUtility = new RsyncUtility();
    $consoleUtility = new ConsoleUtility();
    $optionUtility = new OptionUtility(input()->getOption('options'));
    $dumpCode = $optionUtility->getOption('dumpcode', true);
    $localPath = get('db_storage_path_local');

    runLocally(sprintf(
        'rsync -rz %s --include=%s --exclude=* %s %s',
        $rsyncUtility->getSshOptions(get('argument_host')),
        escapeshellarg('*dumpcode=' . $dumpCode . '*'),
        escapeshellarg($rsyncUtility->getHostWithDbStoragePath(get('argument_host'))),
        escapeshellarg($localPath)
    ));

    $filePathPattern = $localPath . '/*dumpcode=' . $dumpCode . '*';
    $files = runLocally('ls ' . $filePathPattern);
    $files = explode("\n", trim($files));
    if (!empty($files)) {
        $filePath = $files[0];
        $fileSizeBytes = runLocally('stat -c%s ' . escapeshellarg($filePath));
        $fileSizeMB = number_format($fileSizeBytes / (1024 * 1024), 2);
        output()->write($consoleUtility->formattingTaskOutputHeader("Sql file size: "));
        output()->write($consoleUtility->formattingTaskOutputContent(sprintf("%s MB", $fileSizeMB), false));
    }

})->desc('Download the database dumps with given dumpcode from remote to local database dumps storage');
