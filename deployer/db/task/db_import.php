<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\OptionUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-import
 */
task('db:import', function () {
    $consoleUtility = new ConsoleUtility();
    $fileUtility = new FileUtility();
    $optionUtility = new OptionUtility(input()->getOption('options'));
    $dumpCode = $optionUtility->getOption('dumpcode', true);
    if (get('is_argument_host_the_same_as_local_host')) {
        $databaseStoragePathLocal = get('db_storage_path_local');
        foreach (get('db_databases_merged') as $databaseCode => $databaseConfig) {
            $globStart = $databaseStoragePathLocal
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
                $databaseStoragePathLocal
            );

            try {
                // Drop all tables.
                if (empty($optionUtility->getOption('importTaskDoNotDropAllTablesBeforeImport'))) {
                    runLocally(sprintf(
                        '%s --defaults-file=%s %s %s --add-drop-table --no-data | ' .
                        'grep -e \'^DROP \| FOREIGN_KEY_CHECKS\' | %s %s --defaults-file=%s %s -D%s',
                        get('local/bin/mysqldump'),
                        escapeshellarg($tmpMyCnfFile),
                        DatabaseUtility::getSslCliOptions($databaseConfig),
                        escapeshellarg($databaseConfig['dbname']),
                        get('local/bin/mysql'),
                        escapeshellarg($tmpMyCnfFile),
                        DatabaseUtility::getSslCliOptions($databaseConfig),
                        escapeshellarg($databaseConfig['dbname'])
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
                    '%s --defaults-file=%s %s %s -D%s -e%s',
                    get('local/bin/mysql'),
                    escapeshellarg($tmpMyCnfFile),
                    get('db_import_mysql_options_data', ''),
                    DatabaseUtility::getSslCliOptions($databaseConfig),
                    escapeshellarg($databaseConfig['dbname']),
                    escapeshellarg('SOURCE ' . $dataSqlFile[0])
                ));
                $postSqlInCollected = [];
                if ($databaseConfig['post_sql_in_markers'] ?? false) {
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
                if ($databaseConfig['post_sql_in'] ?? false) {
                    $postSqlInCollected[] = $databaseConfig['post_sql_in'];
                }
                if (!empty($postSqlInCollected)) {
                    $importSqlFile = $databaseStoragePathLocal . $dumpCode . '.sql';
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
                        runLocally($postCommand . ' ' . $optionUtility->getOptionsString());
                    }
                }

                if (get('db_import_big_table_info_enable', true)) {
                    $databaseUtility = new DatabaseUtility();
                    $bigTableSizeThreshold = get('db_import_big_table_size_threshold', 50);
                    $bigTables = $databaseUtility->getBigTables($databaseConfig, $bigTableSizeThreshold);

                    if (!empty($bigTables)) {
                        output()->writeln($consoleUtility->formattingTaskOutputHeader('Big tables:'));
                        $bigTablesText = '';
                        $line = '';
                        $lineLength = 0;
                        $maxLineLength = get('db_import_big_table_output_max_line_length', 120);

                        foreach ($bigTables as $tableInfo) {
                            $tableSizeText = $tableInfo['Table'] . ' (' . $tableInfo['Size (MB)'] . ' MB) | ';
                            $tableSizeTextLength = strlen($tableSizeText);

                            if ($lineLength + $tableSizeTextLength > $maxLineLength) {
                                $bigTablesText .= rtrim($line, ' |') . "\n";
                                $line = '';
                                $lineLength = 0;
                            }
                            $line .= $tableSizeText;
                            $lineLength += $tableSizeTextLength;
                        }
                        $bigTablesText .= rtrim($line, ' |');

                        output()->write($consoleUtility->formattingTaskOutputContent($bigTablesText));
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
            $optionUtility->getOptionsString(),
        ];

        $output = run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:import '
            . implode(' ', $params));
        output()->write(preg_replace('/^task db:import\n?/', '', $output));
    }
})->desc('Import dump with given dumpcode from database dumps storage to database');
