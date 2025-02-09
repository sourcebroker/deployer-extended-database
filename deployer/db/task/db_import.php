<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-import
 */
task('db:import', function () {
    $consoleUtility = new ConsoleUtility();
    $dumpCode = $consoleUtility->getOption('dumpcode', true);
    $fileUtility = new FileUtility();
    if (get('is_argument_host_the_same_as_local_host')) {
        $databaseStoragePathLocalNormalised = $fileUtility->normalizeFolder(get('db_storage_path_local'));
        foreach (get('db_databases_merged') as $databaseCode => $databaseConfig) {
            $globStart = $databaseStoragePathLocalNormalised
                . '*dbcode=' . $fileUtility->normalizeFilename($databaseCode)
                . '*dumpcode=' . $dumpCode;

            $structureSqlFile = glob($globStart . '*type=structure.sql');
            if (empty($structureSqlFile)) {
                throw new GracefulShutdownException(
                    'No structure file for --options=dumpcode:' . $dumpCode . '. Glob build: ' .
                    $globStart . '*type=structure.sql',
                    1500718221204
                );
            }
            if (count($structureSqlFile) > 1) {
                throw new GracefulShutdownException('There are more than two structure file for --options=dumpcode:' . $dumpCode .
                    '. Glob build: ' . $globStart . '*type=structure.sql. ' .
                    "\n" . ' Files founded: ' . "\n" . implode("\n", $structureSqlFile), 1500722088929);
            }
            $dataSqlFile = glob($globStart . '*type=data.sql');
            if (empty($dataSqlFile)) {
                throw new GracefulShutdownException(
                    'No data file for --options=dumpcode:' . $dumpCode . '. Glob built: ' .
                    $globStart . '*type=data.sql',
                    1500722093334
                );
            }
            if (count($dataSqlFile) > 1) {
                throw new GracefulShutdownException(
                    'There are more than two data files for --options=dumpcode:' . $dumpCode . '. Glob built: ' .
                    $globStart . '*type=data.sql. ' .
                    "\n" . ' Files founded: ' . "\n" . implode("\n", $dataSqlFile),
                    1500722095323
                );
            }
            $tmpMyCnfFile = DatabaseUtility::getTemporaryMyCnfFile(
                $databaseConfig,
                $databaseStoragePathLocalNormalised
            );

            try {
                // Drop all tables.
                if (empty($consoleUtility->getOption('importTaskDoNotDropAllTablesBeforeImport'))) {
                    runLocally(sprintf(
                        '%s --defaults-file=%s %s --add-drop-table --no-data | ' .
                        'grep -e \'^DROP \| FOREIGN_KEY_CHECKS\' | %s --defaults-file=%s %s -D%s %s',
                        get('local/bin/mysqldump'),
                        escapeshellarg($tmpMyCnfFile),
                        escapeshellarg($databaseConfig['dbname']),
                        get('local/bin/mysql'),
                        escapeshellarg($tmpMyCnfFile),
                        DatabaseUtility::getSslCliOptions($databaseConfig),
                        escapeshellarg($databaseConfig['dbname']),
                        DatabaseUtility::getSslCliOptions($databaseConfig)
                    ));
                }
                // Import dump with database structure.
                runLocally(sprintf(
                    '%s --defaults-file=%s %s %s -D%s -e%s',
                    get('local/bin/mysql'),
                    escapeshellarg($tmpMyCnfFile),
                    get('db_import_mysql_options_structure', ''),
                    DatabaseUtility::getSslCliOptions($databaseConfig),
                    escapeshellarg($databaseConfig['dbname']),
                    escapeshellarg('SOURCE ' . $structureSqlFile[0])
                ));
                // Import dump with data.
                runLocally(sprintf(
                    '%s --defaults-file=%s %s -D%s -e%s',
                    get('local/bin/mysql'),
                    escapeshellarg($tmpMyCnfFile),
                    get('db_import_mysql_options_data', ''),
                    escapeshellarg($databaseConfig['dbname']),
                    escapeshellarg('SOURCE ' . $dataSqlFile[0])
                ));
                $postSqlInCollected = [];
                if (isset($databaseConfig['post_sql_in_markers'])) {
                    // Prepare some markers to use in post_sql_in_markers:
                    $markersArray = [];
                    if (!empty(get('public_urls', []))) {
                        $publicUrlCollected = [];
                        foreach (get('public_urls') as $publicUrl) {
                            if (parse_url($publicUrl, PHP_URL_SCHEME) && parse_url($publicUrl, PHP_URL_HOST)) {
                                $port = '';
                                if (parse_url($publicUrl, PHP_URL_PORT)) {
                                    $port = ':' . parse_url($publicUrl, PHP_URL_PORT);
                                }
                                $publicUrlCollected[] = parse_url($publicUrl, PHP_URL_HOST) . $port;
                            } else {
                                throw new GracefulShutdownException('The configuration setting "public_urls" should have full url like
                        "https://www.example.com" but the value is only "' . $publicUrl . '"', 1491384103020);
                            }
                        }
                        $markersArray['{{domainsSeparatedByComma}}'] = '"' . implode(
                                '","',
                                $publicUrlCollected
                            ) . '"';
                        $markersArray['{{firstDomainWithScheme}}'] = get('public_urls')[0];
                        $markersArray['{{firstDomainWithSchemeAndEndingSlash}}'] = rtrim(get('public_urls')[0],
                                '/') . '/';
                    }
                    $postSqlInCollected[] = str_replace(
                        array_keys($markersArray),
                        $markersArray,
                        $databaseConfig['post_sql_in_markers']
                    );
                }
                if (isset($databaseConfig['post_sql_in'])) {
                    $postSqlInCollected[] = $databaseConfig['post_sql_in'];
                }
                if (!empty($postSqlInCollected)) {
                    $importSqlFile = $databaseStoragePathLocalNormalised . $dumpCode . '.sql';
                    file_put_contents($importSqlFile, implode(' ', $postSqlInCollected));
                    runLocally(sprintf(
                        ' %s --defaults-file=%s %s -D%s -e%s',
                        get('local/bin/mysql'),
                        escapeshellarg($tmpMyCnfFile),
                        get('db_import_mysql_options_post_sql_in', ''),
                        escapeshellarg($databaseConfig['dbname']),
                        escapeshellarg('SOURCE ' . $importSqlFile)
                    ));
                    unlink($importSqlFile);
                }
                if (isset($databaseConfig['post_command']) && is_array($databaseConfig['post_command'])) {
                    foreach ($databaseConfig['post_command'] as $postCommand) {
                        $options = $consoleUtility->getOptionsForCliUsage(['dumpcode' => $dumpCode]);
                        runLocally($postCommand . ' ' . $options);
                    }
                }
            } catch (\Exception $exception) {
                throw new GracefulShutdownException(
                    'Error during import dump with dumpcode: ' . $dumpCode . '. ' . $exception->getMessage(),
                    1500722095323
                );
            } finally {
                unlink($tmpMyCnfFile);
            }
        }
    } else {
        $params = [
            get('argument_host'),
            $consoleUtility->getVerbosityAsParameter(),
            input()->getOption('options') ? '--options=' . input()->getOption('options') : '',
        ];

        $output = run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:import ' . implode(' ',
                $params));
        output()->write(str_replace("task db:import\n", '', $output));
    }
})->desc('Import dump with given dumpcode from database dumps storage to database');
