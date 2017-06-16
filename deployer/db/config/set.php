<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;

set('deployer_version', 4);

set('db_settings_mysqldump_path', function () {
    if (runLocally('if hash mysqldump 2>/dev/null; then echo \'true\'; fi')->toBool()) {
        return 'mysqldump';
    } else {
        throw new \RuntimeException('The mysqldump path on server "' . get('server')['name'] . '" is unknown. You can set it in env var "db_settings_mysqldump_path" . [Error code: 1458412747]');
    }
});

set('db_settings_mysql_path', function () {
    if (runLocally('if hash mysql 2>/dev/null; then echo \'true\'; fi')->toBool()) {
        return 'mysql';
    } else {
        throw new \RuntimeException('The mysql path on server "' . get('server')['name'] . '" is unknown. You can set it in env var "db_settings_mysql_path" . [Error code: 1458412748]');
    }
});

set('databases_config', function () {
    $databaseConfigsMerged = [];
    foreach (get('db_databases') as $databaseIdentifies => $databaseConfigs) {
        $databaseConfigsMerged[$databaseIdentifies] = [];
        foreach ($databaseConfigs as $databaseConfig) {
            if (is_array($databaseConfig)) {
                $databaseConfigsMerged[$databaseIdentifies] = ArrayUtility::arrayMergeRecursiveDistinct($databaseConfigsMerged[$databaseIdentifies], $databaseConfig);
                continue;
            }
            if (file_exists(get('current_dir') . DIRECTORY_SEPARATOR . $databaseConfig)) {
                $mergeArray = include(get('current_dir') . DIRECTORY_SEPARATOR . $databaseConfig);
                $databaseConfigsMerged[$databaseIdentifies] = ArrayUtility::arrayMergeRecursiveDistinct($databaseConfigsMerged[$databaseIdentifies], $mergeArray);
            } else {
                throw new \RuntimeException('The config file does not exists: ' . $databaseConfig);
            }
        }
    }
    return $databaseConfigsMerged;
});

set('bin/deployer', function () {
    $deployerMinimumSizeInBytesForDownloadCheck = 100000;
    set('active_path', get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current'));

    // Figure out what version is expected to be used. "deployer_version" can be either integer "4"
    // or full verison "4.3.0". There is no support for "4.3".
    // TODO: Find the most recent versions automaticaly
    $deployerVersionRequestedByUser = trim(get('deployer_version'));
    if (strpos($deployerVersionRequestedByUser, '.') === false) {
        switch (($deployerVersionRequestedByUser)) {
            case 5:
                $deployerVersionToUse = '5.0.3';
                break;
            case 4:
                $deployerVersionToUse = '4.3.0';
                break;
            case 3:
                $deployerVersionToUse = '3.3.0';
                break;
            case 2:
                $deployerVersionToUse = '2.0.5';
                break;
            default:
                throw new \RuntimeException('You requested deployer version "' . $deployerVersionRequestedByUser . '" 
                but we are not able to determine what exact version is supposed to be chosen. 
                Please set "deployer_version" to full semantic versioning like "' . $deployerVersionRequestedByUser . '.0.1"');
        }
    } else {
        if (count(explode('.', $deployerVersionRequestedByUser)) === 3) {
            $deployerVersionToUse = $deployerVersionRequestedByUser;
        } else {
            throw new \RuntimeException('Deployer version must be set to just "major" like "4" which means give me most '
                . 'fresh version for deployer 4 or "major.minor.path" like "4.3.0". The "' . $deployerVersionRequestedByUser
                . '" is not supported.');
        }
    }
    set('deployer_filename', 'deployer-' . $deployerVersionToUse . '.phar');
    if (test('[ -f {{deploy_path}}/shared/{{deployer_filename}} ]')) {
        $downloadedFileSizeInBytes = trim(run('wc -c  < {{deploy_path}}/shared/{{deployer_filename}}')->toString());
        if ($downloadedFileSizeInBytes < $deployerMinimumSizeInBytesForDownloadCheck) {
            writeln(parse('Removing {{deploy_path}}/shared/{{deployer_filename}}" because the file size when last '
                . 'downloaded was ' . $downloadedFileSizeInBytes . ' bytes so downloads was probably not sucessful.'));
            run('rm -f {{deploy_path}}/shared/{{deployer_filename}}');
        }
    }
    //If we do not have yet this version fo deployer in shared then download it
    if (!test('[ -f {{deploy_path}}/shared/{{deployer_filename}} ]')) {
        set('deployer_download_link', 'https://deployer.org/releases/v' . $deployerVersionToUse . '/deployer.phar');
        run('cd {{deploy_path}}/shared && curl -o {{deployer_filename}} -L {{deployer_download_link}} && chmod 775 {{deployer_filename}}');
        //Simplified checker if deployer has been downloaded or not. Just check size of file which should be at least 200kB.
        $downloadedFileSizeInBytes = trim(run('wc -c  < {{deploy_path}}/shared/{{deployer_filename}}')->toString());
        if ($downloadedFileSizeInBytes > $deployerMinimumSizeInBytesForDownloadCheck) {
            run("cd {{deploy_path}}/shared && chmod 775 {{deployer_filename}}");
        } else {
            throw new \RuntimeException(parse('Downloaded deployer has size ' . $downloadedFileSizeInBytes . ' bytes. It seems'
                . ' like the download was unsucessfull. The file downloaded was: "{{deployer_download_link}}".'
                . 'Please check if this link return file. The downloaded content was stored in '
                . '{{deploy_path}}/shared/{{deployer_filename}}'));
        }
    }
    //Rebuild symlink of $deployerFilename to "{{active_path}}/deployer.phar"
    run("rm -f {{active_path}}/deployer.phar && cd {{active_path}} && {{bin/symlink}} {{deploy_path}}/shared/{{deployer_filename}} {{active_path}}/deployer.phar");
    if (test('[ -f {{active_path}}/deployer.phar ]')) {
        $deployerBin = parse('{{active_path}}/deployer.phar');
    } else {
        throw new \RuntimeException(parse('Can not create symlink from {{deploy_path}}/shared/{{deployer_filename}} to {{active_path}}/deployer.phar'));
    }
    return $deployerBin;
});