<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ArrayUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use Deployer\Exception\GracefulShutdownException;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-export
 */
task('db:export', function () {
    $consoleUtility = new ConsoleUtility();
    $dumpCode = $consoleUtility->getOption('dumpcode', true);
    $fileUtility = new FileUtility();
    $arrayUtility = new ArrayUtility();
    $databaseUtility = new DatabaseUtility();
    if (get('is_argument_host_the_same_as_local_host')) {
        foreach (get('db_databases_merged') as $databaseCode => $databaseConfig) {
            $filenameParts = [
                'dateTime' => date('Y-m-d_H-i-s'),
                'server' => 'server=' . $fileUtility->normalizeFilename(get('local_host')),
                'dbcode' => 'dbcode=' . $fileUtility->normalizeFilename($databaseCode),
                'dumpcode' => 'dumpcode=' . $fileUtility->normalizeFilename($dumpCode),
                'type' => '',
            ];
            $databaseStoragePathLocalNormalised = $fileUtility->normalizeFolder(get('db_storage_path_local'));
            $tmpMyCnfFile = DatabaseUtility::getTemporaryMyCnfFile(
                $databaseConfig,
                $databaseStoragePathLocalNormalised
            );
            $mysqlDumpArgs = [
                'local/bin/mysqldump' => get('local/bin/mysqldump'),
                escapeshellarg($tmpMyCnfFile),
                'options' => '',
                'dbname' => escapeshellarg($databaseConfig['dbname']),
                'absolutePath' => '',
                'ignore-tables' => '',
            ];

            if (isset($databaseConfig['ignore_tables_out']) && is_array($databaseConfig['ignore_tables_out'])) {
                $ignoreTables = $arrayUtility->filterWithRegexp(
                    $databaseConfig['ignore_tables_out'],
                    $databaseUtility->getTables($databaseConfig)
                );
                if (!empty($ignoreTables)) {
                    if (get('db_export_mysqldump_show_ignore_tables_out', true)) {
                        $ignoredTablesText = '';
                        $line = '';
                        $lineLength = 0;
                        $maxLineLength = get('db_export_mysqldump_show_ignore_tables_out_max_line_length', 120);

                        foreach ($ignoreTables as $table) {
                            $tableLength = strlen($table) + 3;
                            if ($lineLength + $tableLength > $maxLineLength) {
                                $ignoredTablesText .= rtrim($line, ' |') . "\n";
                                $line = '';
                                $lineLength = 0;
                            }
                            $line .= $table . ' | ';
                            $lineLength += $tableLength;
                        }
                        $ignoredTablesText = rtrim($ignoredTablesText);

                        output()->writeln($consoleUtility->formattingTaskOutputHeader('Ignored tables:'));
                        output()->write($consoleUtility->formattingTaskOutputContent($ignoredTablesText));
                    }
                    $mysqlDumpArgs['ignore-tables'] = implode(' ', array_map(function ($table) use ($databaseConfig) {
                        return '--ignore-table=' . escapeshellarg($databaseConfig['dbname'] . '.' . $table);
                    }, $ignoreTables));
                }
            }

            try {
                // dump database structure
                $filenameParts['type'] = 'type=structure';
                $mysqlDumpArgs['options'] = get('db_export_mysqldump_options_structure', '');
                $mysqlDumpArgs['options'] .= DatabaseUtility::getSslCliOptions($databaseConfig);
                $mysqlDumpArgs['absolutePath'] = escapeshellarg($databaseStoragePathLocalNormalised . implode('#',
                        $filenameParts) . '.sql');

                runLocally(vsprintf(
                    '%s --defaults-file=%s %s %s -r%s'
                    . ($consoleUtility->getOption('exportTaskAddIgnoreTablesToStructureDump') ? ' %s' : ''),
                    $mysqlDumpArgs
                ));

                // dump database data
                $filenameParts['type'] = 'type=data';
                $mysqlDumpArgs['options'] = get('db_export_mysqldump_options_data', '');
                $mysqlDumpArgs['options'] .= DatabaseUtility::getSslCliOptions($databaseConfig);
                $mysqlDumpArgs['absolutePath'] = escapeshellarg($databaseStoragePathLocalNormalised . implode('#',
                        $filenameParts) . '.sql');
                runLocally(vsprintf(
                    '%s --defaults-file=%s %s %s -r%s %s',
                    $mysqlDumpArgs
                ));
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
        $output = run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:export ' . implode(' ',
                $params));
        output()->write(str_replace("task db:export\n", '', $output));
    }
})->desc('Dump database and store it in database dumps storage');

