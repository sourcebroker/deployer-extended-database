<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerInstance\Configuration;
use Deployer\Exception\GracefulShutdownException;

set('db_export_mysqldump_options_structure', '--no-data=true --default-character-set=utf8 --no-tablespaces');

set('db_export_mysqldump_options_data', '--opt --skip-lock-tables --single-transaction --no-create-db --default-character-set=utf8 --no-tablespaces');

set('db_import_mysql_options_structure', '--default-character-set=utf8');

set('db_import_mysql_options_data', '--default-character-set=utf8');

set('db_process_commands', [
    // @see http://stackoverflow.com/a/38595160/1588346
//    'remove_definer' => 'sed --version >/dev/null 2>&1 ' .
//        '&& sed -i -- \'s/DEFINER=[^*]*\*/\*/g\' {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*.sql ' .
//        '|| sed -i \'\' \'s/DEFINER=[^*]*\*/\*/g\' {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*.sql'
]);

set('db_compress_suffix', '.gz');

set('db_compress_command', [
    '{{local/bin/gzip}} --force --name {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*.sql --suffix ' . get('db_compress_suffix')
]);

set('db_decompress_command', [
    '{{local/bin/gzip}} --force --name --uncompress ' . ' --suffix ' . get('db_compress_suffix') . ' {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*' . get('db_compress_suffix')
]);

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
                $dbConfigsMerged[$dbIdentifier] = $arrayUtility->arrayMergeRecursiveDistinct(
                    $dbConfigsMerged[$dbIdentifier],
                    $mergeArray
                );
            }
            if (is_string($dbConfig)) {
                if (file_exists($dbConfig)) {
                    $mergeArray = include($dbConfig);
                    $dbConfigsMerged[$dbIdentifier] = $arrayUtility->arrayMergeRecursiveDistinct(
                        $dbConfigsMerged[$dbIdentifier],
                        $mergeArray
                    );
                } else {
                    throw new GracefulShutdownException('The config file does not exists: ' . $dbConfig);
                }
            }
        }
    }
    return $dbConfigsMerged;
});

// Returns path to store database dumps on local stage.
set('db_storage_path_local', function () {
    if (Configuration::getLocalHost()->getConfig()->get('db_storage_path_relative', false) == false) {
        $dbStoragePathLocal = Configuration::getLocalHost()->getConfig()->get('deploy_path') . '/.dep/database/dumps';
    } else {
        $dbStoragePathLocal = Configuration::getLocalHost()->getConfig()->get('deploy_path') . '/'
            . Configuration::getLocalHost()->getConfig()->get('db_storage_path_relative');
    }
    runLocally('[ -d ' . $dbStoragePathLocal . ' ] || mkdir -p ' . $dbStoragePathLocal);
    return $dbStoragePathLocal;
});

// Returns path to store database dumps on remote stage.
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
    return (new FileUtility())->locateLocalBinaryPath('mysqldump');
});

set('local/bin/mysql', function () {
    return (new FileUtility())->locateLocalBinaryPath('mysql');
});

set('local/bin/gzip', function () {
    return (new FileUtility())->locateLocalBinaryPath('gzip');
});
