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
        $rsyncUtility->getSshOptions(get('target_stage')) ? '-e '
            . escapeshellarg($rsyncUtility->getSshOptions(get('target_stage'))) : '',
        escapeshellarg('*dumpcode=' . $dumpCode . '*'),
        escapeshellarg($fileUtility->normalizeFolder(get('db_storage_path_current'))),
        escapeshellarg($rsyncUtility->getHostWithDbStoragePath(get('target_stage')))
    ), 0);
})->desc('Upload the database dumps for given dumpcode from current to target database dumps storage.');
