<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerInstance\Configuration;
use Deployer\Exception\GracefulShutdownException;

// mysqldump options for dumping structure.
set('db_export_mysqldump_options_structure', '--no-data=true --default-character-set=utf8');

// mysqldump options for dumping data.
set('db_export_mysqldump_options_data',
    '--opt --skip-lock-tables --single-transaction --no-create-db --default-character-set=utf8');

// mysql options for importing structure.
set('db_import_mysql_options_structure', '--default-character-set=utf8');

// mysql options for importing data.
set('db_import_mysql_options_data', '--default-character-set=utf8');

// Return commands for direct processing of sql file. Can be used before mysql import.
set('db_process_commands', [
    // @see http://stackoverflow.com/a/38595160/1588346
    'remove_definer' => 'sed --version >/dev/null 2>&1 ' .
        '&& sed -i -- \'s/DEFINER=[^*]*\*/\*/g\' {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*.sql ' .
        '|| sed -i \'\' \'s/DEFINER=[^*]*\*/\*/g\' {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*.sql'
]);

set('db_compress_suffix', '.gz');

// Return commands for compressing sql file.
set('db_compress_command', [
    '{{local/bin/gzip}} --force --name {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*.sql --suffix ' . get('db_compress_suffix')
]);
// Return commands for compressing sql file.
set('db_decompress_command', [
    '{{local/bin/gzip}} --force --name --uncompress ' . ' --suffix ' . get('db_compress_suffix') . ' {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*' . get('db_compress_suffix')
]);

// Returns current server configuration.
set('current_server', function () {
    return Configuration::getServer(get('current_stage'));
});

set('current_environment', function () {
    return Configuration::getEnvironment(get('current_stage'));
});

// Returns target stage server configuration.
set('target_server', function () {
    return Configuration::getServer(get('target_stage'));
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
                    throw new ConfigurationException('The config file does not exists: ' . $dbConfig);
                }
            }
        }
    }
    return $dbConfigsMerged;
});

// Returns path to store database dumps on current instance.
set('db_storage_path_current', function () {
    if (get('current_environment')->get('db_storage_path_relative', false) == false) {
        $dbStoragePathCurrent = get('current_environment')->get('deploy_path') . '/.dep/database/dumps';
    } else {
        $dbStoragePathCurrent = get('current_environment')->get('deploy_path') . '/'
            . get('current_environment')->get('db_storage_path_relative');
    }
    runLocally('[ -d ' . $dbStoragePathCurrent . ' ] || mkdir -p ' . $dbStoragePathCurrent);
    return $dbStoragePathCurrent;
});

// Returns path to store database dumps on target stage instance.
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
    $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
    // We need check if there is composer based deployer
    if (test('[ -e ' . escapeshellarg($activePath . '/vendor/bin/dep') . ' ]')) {
        $deployerBin = $activePath . '/vendor/bin/dep';
    } else {
        throw new GracefulShutdownException('There must be ' . $activePath . '/vendor/bin/dep phar file, but it could not be found.');
    }
    return $deployerBin;
});

set('local/bin/deployer', function () {
    return './vendor/bin/dep';
});

set('local/bin/mysqldump', function () {
    if (testLocally('hash mysqldump 2>/dev/null')) {
        return 'mysqldump';
    } else {
        throw new GracefulShutdownException('The mysqldump path on server "' . get('target_stage') . '" is unknown. 
        You can set it in env var "local/bin/mysqldump"', 1500717760352);
    }
});

set('local/bin/mysql', function () {
    if (testLocally('hash mysql 2>/dev/null')) {
        return 'mysql';
    } else {
        throw new GracefulShutdownException('The mysql path on server "' . get('target_stage') . '" is unknown.
        You can set it in env var "local/bin/mysql".', 1500717744659);
    }
});

set('local/bin/gzip', function () {
    if (testLocally('hash gzip 2>/dev/null')) {
        return 'gzip';
    } else {
        throw new GracefulShutdownException('The gzip path on server "' . get('target_stage') . '" is unknown. 
        You can set it in env var "local/bin/gzip"', 1512217259381);
    }
});
