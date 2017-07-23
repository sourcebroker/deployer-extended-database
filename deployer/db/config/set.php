<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\InstanceUtility;

// deployer settings
set('default_stage', function () {
    return (new InstanceUtility)->getCurrentInstance();
});

// Return what deployer to download on source server when we use phar deployer.
set('db_deployer_version', 4);

// Return current instance name. Based on that scripts knows from which server() takes the data to database.
set('db_instance', function () {
    return (new InstanceUtility)->getCurrentInstance();
});

// mysqldump options for dumping structure.
set('db_export_mysqldump_options_structure',
    '--no-data=true --default-character-set=utf8');

// mysqldump options for dumping data.
set('db_export_mysqldump_options_data',
    '--opt --skip-lock-tables --single-transaction --no-create-db --default-character-set=utf8');

// mysql options for dumping structure.
set('db_import_mysql_options_structure',
    '--default-character-set=utf8');

// mysql options for dumping data.
set('db_import_mysql_options_data',
    '--default-character-set=utf8');

// Return commands for direct processing of sql file. can be used before mysql import.
set('db_process_commands', [
    // @see http://stackoverflow.com/a/38595160/1588346
    'remove_definer' => 'sed --version >/dev/null 2>&1 ' .
        '&& sed -i -- \'s/DEFINER=[^*]*\*/\*/g\' {{databaseStorageAbsolutePath}}/*dumpcode:{{dumpcode}}*.sql ' .
        '|| sed -i \'\' \'s/DEFINER=[^*]*\*/\*/g\' {{databaseStorageAbsolutePath}}/*dumpcode:{{dumpcode}}*.sql'
]);

// Returns current server configuration.
set('db_current_server', function () {
    try {
        $currentServer = Deployer::get()->environments[get('db_instance')];
    } catch (\RuntimeException $e) {
        $servers = '';
        $i = 1;
        foreach (Deployer::get()->environments as $key => $server) {
            $servers .= "\n" . $i++ . '. ' . $key;
        }
        throw new \RuntimeException('Name of instance "' . get('db_instance') . '" is not on the server list:' .
            $servers . "\n" . 'Please check case sensitive.', 1500717628491);
    }
    return $currentServer;
});

// Returns "db_databases" merged for direct use.
set('db_databases_merged', function () {
    $arrayUtility = new ArrayUtility();
    $dbConfigsMerged = [];
    foreach (get('db_databases') as $dbIdentifier => $dbConfigs) {
        $dbConfigsMerged[$dbIdentifier] = [];
        foreach ($dbConfigs as $dbConfig) {
            if (is_array($dbConfig)) {
                $dbConfigsMerged[$dbIdentifier]
                    = $arrayUtility->arrayMergeRecursiveDistinct($dbConfigsMerged[$dbIdentifier], $dbConfig);
                continue;
            }
            if (is_object($dbConfig) && ($dbConfig instanceof \Closure)) {
                $mergeArray = call_user_func($dbConfig);
                $dbConfigsMerged[$dbIdentifier] = $arrayUtility->arrayMergeRecursiveDistinct($dbConfigsMerged[$dbIdentifier],
                    $mergeArray);
            }
            if (is_string($dbConfig)) {
                if (file_exists($dbConfig)) {
                    $mergeArray = include($dbConfig);
                    $dbConfigsMerged[$dbIdentifier] = $arrayUtility->arrayMergeRecursiveDistinct($dbConfigsMerged[$dbIdentifier],
                        $mergeArray);
                } else {
                    throw new \RuntimeException('The config file does not exists: ' . $dbConfig);
                }
            }
        }
    }
    return $dbConfigsMerged;
});

// Returns path to store database dumps on current instance.
set('db_storage_path_current', function () {
    if (get('db_current_server')->get('db_storage_path_relative', false) == false) {
        $dbStoragePathCurrent = get('db_current_server')->get('deploy_path') . '/.dep/database/dumps';
    } else {
        $dbStoragePathCurrent = get('db_current_server')->get('deploy_path') . '/'
            . get('db_current_server')->get('db_storage_path_relative');
    }
    runLocally('[ -d ' . $dbStoragePathCurrent . ' ] || mkdir -p ' . $dbStoragePathCurrent);
    return $dbStoragePathCurrent;
});

// Returns path to store database dumps on remote instance.
set('db_storage_path', function () {
    if (get('db_storage_path_relative', false) == false) {
        $dbStoragePath = get('deploy_path') . '/.dep/database/dumps';
    } else {
        $dbStoragePath = get('deploy_path') . '/' . get('db_storage_path_relative');
    }
    run('[ -d ' . $dbStoragePath . ' ] || mkdir -p ' . $dbStoragePath);
    return $dbStoragePath;
});

set('bin/deployer', function () {
    $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]')
            ? 'release' : 'current');
    //check if there is composer based deployer
    if (test('[ -e ' . escapeshellarg($activePath . '/vendor/bin/dep') . ' ]')) {
        $deployerBin = $activePath . '/vendor/bin/dep';
    } else {
        $deployerMinimumSizeInBytesForDownloadCheck = 100000;
        // Figure out what version is expected to be used. "db_deployer_version" can be either integer "4"
        // or full verison "4.3.0". There is no support for "4.3".
        // TODO: Find the most recent versions automaticaly
        $deployerVersionRequestedByUser = trim(get('db_deployer_version'));
        if (strpos($deployerVersionRequestedByUser, '.') === false) {
            switch (($deployerVersionRequestedByUser)) {
                case 5:
                    $deployerVersionToUse = '5.0.3';
                    break;
                case 4:
                    $deployerVersionToUse = '4.3.1';
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
                Please set "db_deployer_version" to full semantic versioning like "' . $deployerVersionRequestedByUser . '.0.1"',
                        1500717666998);
            }
        } else {
            if (count(explode('.', $deployerVersionRequestedByUser)) === 3) {
                $deployerVersionToUse = $deployerVersionRequestedByUser;
            } else {
                throw new \RuntimeException('Deployer version must be set to just "major" like "4" which means give me most '
                    . 'fresh version for deployer 4 or "major.minor.path" like "4.3.0". The "' . $deployerVersionRequestedByUser
                    . '" is not supported.', 1500717685169);
            }
        }
        $deployerFilename = 'deployer-' . $deployerVersionToUse . '.phar';
        $deployerFilenameFullPath = get('deploy_path') . '/shared/' . $deployerFilename;
        if (test('[ -f ' . $deployerFilenameFullPath . ' ]')) {
            $downloadedFileSizeInBytes = trim(run('wc -c  < {{deploy_path}}/shared/' . $deployerFilename)->toString());
            if ($downloadedFileSizeInBytes < $deployerMinimumSizeInBytesForDownloadCheck) {
                writeln(parse('Removing {{deploy_path}}/shared/' . $deployerFilename . ' because the file size when last '
                    . 'downloaded was ' . $downloadedFileSizeInBytes . ' bytes so downloads was probably not sucessful.'));
                run('rm -f {{deploy_path}}/shared/' . $deployerFilename);
            }
        }
        //If we do not have yet this version fo deployer in shared then download it
        if (!test('[ -f ' . $deployerFilenameFullPath . ' ]')) {
            $deployerDownloadLink = 'https://deployer.org/releases/v' . $deployerVersionToUse . '/deployer.phar';
            run('cd {{deploy_path}}/shared && curl -o ' . $deployerFilename . ' -L ' . $deployerDownloadLink . ' && chmod 775 ' . $deployerFilename);
            //Simplified checker if deployer has been downloaded or not. Just check size of file which should be at least 200kB.
            $downloadedFileSizeInBytes = trim(run('wc -c  < ' . $deployerFilenameFullPath)->toString());
            if ($downloadedFileSizeInBytes > $deployerMinimumSizeInBytesForDownloadCheck) {
                run("cd {{deploy_path}}/shared && chmod 775 " . $deployerFilename);
            } else {
                throw new \RuntimeException(parse('Downloaded deployer has size ' . $downloadedFileSizeInBytes . ' bytes. It seems'
                    . ' like the download was unsucessfull. The file downloaded was: "' . $deployerDownloadLink . '".'
                    . 'Please check if this link return file. The downloaded content was stored in ' . $deployerFilenameFullPath),
                    1500717708109);
            }
        }
        //Rebuild symlink of $deployerFilename to $active_path/deployer.phar
        run('rm -f ' . $activePath . '/deployer.phar && cd ' . $activePath . ' && {{bin/symlink}} ' . $deployerFilenameFullPath . $activePath . '/deployer.phar');
        if (test('[ -f ' . $activePath . '/deployer.phar ]')) {
            $deployerBin = $activePath . '/deployer.phar';
        } else {
            throw new \RuntimeException(parse('Can not create symlink from ' . $deployerFilenameFullPath . ' to ' . $activePath . '/deployer.phar'));
        }
    }
    return $deployerBin;
});

// Local call of deployer can be not standard. For example someone could have "dep3" and "dep4" symlinks and call
// "dep3 deploy live". He could expect then that if we will use deployer call inside task we will use then "dep3" and not "dep"
// so we store actual way of calling deployer into "local/bin/deployer" var to use it whenever we call local deployer again in tasks.
set('local/bin/deployer', function () {
    if ($_SERVER['_'] == $_SERVER['PHP_SELF']) {
        return $_SERVER['_'];
    } else {
        return $_SERVER['_'] . ' ' . $_SERVER['PHP_SELF'];
    }
});

set('local/bin/mysqldump', function () {
    if (runLocally('if hash mysqldump 2>/dev/null; then echo \'true\'; fi')->toBool()) {
        return 'mysqldump';
    } else {
        throw new \RuntimeException('The mysqldump path on server "' . get('server')['name'] . '" is unknown. 
        You can set it in env var "local/bin/mysqldump"', 1500717760352);
    }
});

set('local/bin/mysql', function () {
    if (runLocally('if hash mysql 2>/dev/null; then echo \'true\'; fi')->toBool()) {
        return 'mysql';
    } else {
        throw new \RuntimeException('The mysql path on server "' . get('server')['name'] . '" is unknown. 
        You can set it in env var "local/bin/mysql".', 1500717744659);
    }
});
