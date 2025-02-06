<?php

namespace Deployer;

use Closure;
use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerInstance\Configuration;
use Deployer\Exception\GracefulShutdownException;

set('db_export_mysqldump_options_structure', '--no-data=true --default-character-set=utf8mb4 --no-tablespaces');

set('db_export_mysqldump_options_data',
    '--opt --skip-lock-tables --single-transaction --no-create-db --default-character-set=utf8mb4 --no-tablespaces');

set('db_import_mysql_options_structure', '--default-character-set=utf8mb4');

set('db_import_mysql_options_data', '--default-character-set=utf8mb4');

set('db_import_mysql_options_post_sql_in', '--default-character-set=utf8mb4');

set('db_process_commands', [
    // @see http://stackoverflow.com/a/38595160/1588346
//    'remove_definer' => 'sed --version >/dev/null 2>&1 ' .
//        '&& sed -i -- \'s/DEFINER=[^*]*\*/\*/g\' {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*.sql ' .
//        '|| sed -i \'\' \'s/DEFINER=[^*]*\*/\*/g\' {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*.sql'
]);

set('db_compress_suffix', '.gz');

set('db_compress_command', [
    '{{local/bin/gzip}} --force --name {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*.sql --suffix ' . get('db_compress_suffix'),
]);

set('db_decompress_command', [
    '{{local/bin/gzip}} --force --name --uncompress ' . ' --suffix ' . get('db_compress_suffix') . ' {{databaseStorageAbsolutePath}}/*dumpcode={{dumpcode}}*' . get('db_compress_suffix'),
]);

// Returns "db_databases" merged for direct use.
set('db_databases_merged', function () {
    $arrayUtility = new ArrayUtility();
    $dbConfigsMerged = [];
    $dbDatabases = get('db_databases', []);
    $dbDatabasesOverwriteGlobal = get('db_databases_overwrite_global');
    if ($dbDatabasesOverwriteGlobal) {
        $dbDatabases = $arrayUtility->arrayMergeRecursiveDistinct($dbDatabases, $dbDatabasesOverwriteGlobal);
    }
    $dbDatabasesOverwrite = get('db_databases_overwrite');
    if ($dbDatabasesOverwrite) {
        $dbDatabases = $arrayUtility->arrayMergeRecursiveDistinct($dbDatabases, $dbDatabasesOverwrite);
    }
    foreach ($dbDatabases as $dbIdentifier => $dbConfigs) {
        $dbConfigsMerged[$dbIdentifier] = [];

        foreach ($dbConfigs as $dbConfig) {
            if (is_array($dbConfig)) {
                $dbConfigsMerged[$dbIdentifier]
                    = $arrayUtility->arrayMergeRecursiveDistinct($dbConfigsMerged[$dbIdentifier], $dbConfig);
                continue;
            }
            if ($dbConfig instanceof Closure) {
                $mergeArray = $dbConfig();
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
    if (Configuration::getLocalHost()->get('db_storage_path_relative', false) === false) {
        $dbStoragePathLocal = Configuration::getLocalHost()->get('deploy_path') . '/.dep/database/dumps';
    } else {
        $dbStoragePathLocal = Configuration::getLocalHost()->get('deploy_path') . '/'
            . Configuration::getLocalHost()->get('db_storage_path_relative');
    }
    runLocally('[ -d ' . $dbStoragePathLocal . ' ] || mkdir -p ' . $dbStoragePathLocal);

    return $dbStoragePathLocal;
});

// Returns path to store database dumps on remote stage.
set('db_storage_path', function () {
    if (get('db_storage_path_relative', false) === false) {
        $dbStoragePath = get('deploy_path') . '/.dep/database/dumps';
    } else {
        $dbStoragePath = get('deploy_path') . '/' . get('db_storage_path_relative');
    }
    run('[ -d ' . $dbStoragePath . ' ] || mkdir -p ' . $dbStoragePath);

    return $dbStoragePath;
});

set('bin/deployer', function () {
    $deployerBin = get('release_or_current_path') . '/vendor/bin/dep';
    if (!test('[ -e ' . escapeshellarg($deployerBin) . ' ]')) {
        throw new GracefulShutdownException('There must be ' . $deployerBin . ' phar file, but it could not be found.');
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
