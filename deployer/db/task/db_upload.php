<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\OptionUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\RsyncUtility;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-upload
 */
task('db:upload', function () {
    $rsyncUtility = new RsyncUtility();
    $consoleUtility = new ConsoleUtility();
    $optionUtility = new OptionUtility(input()->getOption('options'));
    $dumpCode = $optionUtility->getOption('dumpcode', true);
    $localPath = get('db_storage_path_local');

    runLocally(sprintf(
        'rsync -rz %s --include=%s --exclude=* %s %s',
        $rsyncUtility->getSshOptions(get('argument_host')),
        escapeshellarg('*dumpcode=' . $dumpCode . '*'),
        escapeshellarg($localPath),
        escapeshellarg($rsyncUtility->getHostWithDbStoragePath(get('argument_host')))
    ));

    if (get('db_upload_info_enable', true)) {
        $filePathPattern = $localPath . '/*dumpcode=' . $dumpCode . '*';
        $files = glob($filePathPattern);
        if (!empty($files)) {
            $totalSizeBytes = 0;
            foreach ($files as $filePath) {
                $totalSizeBytes += filesize($filePath);
            }
            $totalSizeMB = number_format($totalSizeBytes / (1024 * 1024), 2);
            output()->write($consoleUtility->formattingTaskOutputHeader("Transferred files size: "));
            output()->write($consoleUtility->formattingTaskOutputContent(sprintf("%s MB", $totalSizeMB), false));
        }
    }

})->desc('Upload the database dump for given dumpcode from local to remote database dumps storage');
