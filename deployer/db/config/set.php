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

set('db_pregmatch_dumpcode', '/^[a-zA-Z0-9_]+$/');

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
    $dbStoragePathLocal = (new FileUtility())->resolveHomeDirectoryLocal($dbStoragePathLocal);
    runLocally('[ -d ' . $dbStoragePathLocal . ' ] || mkdir -p ' . $dbStoragePathLocal);

    return rtrim($dbStoragePathLocal, '/') . '/';
});

// Returns path to store database dumps on remote stage.
set('db_storage_path', function () {
    if (get('db_storage_path_relative', false) === false) {
        $dbStoragePath = get('deploy_path') . '/.dep/database/dumps';
    } else {
        $dbStoragePath = get('deploy_path') . '/' . get('db_storage_path_relative');
    }
    $dbStoragePath = (new FileUtility())->resolveHomeDirectory($dbStoragePath);
    run('[ -d ' . $dbStoragePath . ' ] || mkdir -p ' . $dbStoragePath);

    return rtrim($dbStoragePath, '/') . '/';
});

set('bin/deployer', function () {
    $deployerBin = get('release_or_current_path') . '/vendor/bin/dep';
    if (!test('[ -e ' . $deployerBin . ' ]')) {
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

set('local/bin/php', function () {
    $rawPhpVersion = null;
    if (currentHost()->hasOwn('php_version')) {
        $rawPhpVersion = get('php_version');
    }

    if (empty($rawPhpVersion) && file_exists('composer.json')) {
        try {
            $composerJson = json_decode(file_get_contents('composer.json'), true);
            if (is_array($composerJson)) {
                if (isset($composerJson['config']['platform']['php'])) {
                    $rawPhpVersion = $composerJson['config']['platform']['php'];
                }
                if (empty($rawPhpVersion) && isset($composerJson['require']['php'])) {
                    $rawPhpVersion = $composerJson['require']['php'];
                }
            }
        } catch (\Throwable $e) {
            // Silently handle any errors reading composer.json
        }
    }

    $phpVersionMajorMinor = null;
    if (!empty($rawPhpVersion) && preg_match('/[^0-9]*(\d+)(?:\.(\d+))?(?:\.\d+)?/', $rawPhpVersion, $matches)) {
        if (isset($matches[1]) && is_numeric($matches[1])) {
            $phpVersionMajorMinor = $matches[1] . (isset($matches[2]) && is_numeric($matches[2]) ? '.' . $matches[2] : '.0');
        }
    }

    $fileUtility = new FileUtility();
    if ($phpVersionMajorMinor !== null) {
        try {
            return $fileUtility->locateLocalBinaryPath('php' . $phpVersionMajorMinor);
        } catch (\Throwable $e) {
            try {
                return $fileUtility->locateLocalBinaryPath('php' . str_replace('.', '', $phpVersionMajorMinor));
            } catch (\Throwable $e) {
                output()->writeln(
                    '<comment>PHP binary with version ' . $phpVersionMajorMinor . ' not found, falling back to search for "php"</comment>',
                    \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE
                );
            }
        }
    }

    try {
        $phpBinaryPath = $fileUtility->locateLocalBinaryPath('php');
        $actualVersionMajorMinor = trim(runLocally($phpBinaryPath . ' -r "echo PHP_MAJOR_VERSION.\".\" . PHP_MINOR_VERSION;"'));

        if ($phpVersionMajorMinor !== null && $actualVersionMajorMinor !== $phpVersionMajorMinor) {
            $phpVersionStrict = get('php_version_strict', false);
            if ($phpVersionStrict) {
                throw new \RuntimeException(sprintf(
                    'PHP version mismatch: required %s, found %s',
                    $phpVersionMajorMinor,
                    $actualVersionMajorMinor
                ), 1715438658);
            }
            output()->writeln(
                '<warning>Found PHP binary version (' . $phpBinaryPath . ' ' . $actualVersionMajorMinor . ') does not match required version (' . $phpVersionMajorMinor . ')</warning>',
                \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL
            );
        }
        return $phpBinaryPath;
    } catch (\Throwable $e) {
        if ($e->getCode() === 1715438658) {
            throw $e;
        }

        output()->writeln(
            '<comment>"php" command not found in PATH, using just "php" directly</comment>',
            \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE
        );
        $phpBinaryPath = 'php';
        if ($phpVersionMajorMinor !== null) {
            $actualVersionMajorMinor = trim(runLocally($phpBinaryPath . ' -r "echo PHP_MAJOR_VERSION.\".\" . PHP_MINOR_VERSION;"'));

            if ($actualVersionMajorMinor !== $phpVersionMajorMinor) {
                $phpVersionStrict = get('php_version_strict', false);
                if ($phpVersionStrict) {
                    throw new \RuntimeException(sprintf(
                        'PHP version mismatch: required %s, found %s',
                        $phpVersionMajorMinor,
                        $actualVersionMajorMinor
                    ), 1715438658);
                }
                output()->writeln(
                    '<warning>PHP version found when running just "php" directly ( ' . $actualVersionMajorMinor . ') does not match required version (' . $phpVersionMajorMinor . ')</warning>',
                    \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL
                );
            }
        }
        return $phpBinaryPath;
    }
});
