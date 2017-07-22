<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;

task('db:import', function () {
    if (input()->getOption('dumpcode')) {
        $dumpCode = input()->getOption('dumpcode');
    } else {
        throw new \RuntimeException('No dumpcode set.', 1458937128560);
    }
    $fileUtility = new FileUtility();
    if (get('db_instance') == get('server')['name']) {

        $currentInstanceDatabaseStoragePath = get('db_storage_path_current');
        foreach (get('db_databases_merged') as $databaseCode => $databaseConfig) {
            $globStart = $fileUtility->normalizeFolder($currentInstanceDatabaseStoragePath)
                . '*dbcode:' . $fileUtility->normalizeFilename($databaseCode)
                . '*dumpcode:' . $dumpCode;

            $structureSqlFile = glob($globStart . '*type:structure.sql');
            if (empty($structureSqlFile)) {
                throw new \RuntimeException('No structure file for --dumpcode=' . $dumpCode . '. Glob build: ' .
                    $globStart . '*type:structure.sql',
                    1500718221204);
            }
            if (count($structureSqlFile) > 1) {
                throw new \RuntimeException('There are more than two structure file for --dumpcode=' . $dumpCode .
                    '. Glob build: ' . $globStart . '*type:structure.sql. ' .
                    "\n" . ' Files founded: ' . "\n" . implode("\n", $structureSqlFile), 1500722088929);
            }
            $dataSqlFile = glob($globStart . '*type:data.sql');
            if (empty($dataSqlFile)) {
                throw new \RuntimeException('No data file for --dumpcode=' . $dumpCode . '. Glob built: ' .
                    $globStart . '*type:data.sql',
                    1500722093334);
            }
            if (count($dataSqlFile) > 1) {
                throw new \RuntimeException('There are more than two data files for --dumpcode=' . $dumpCode . '. Glob built: ' .
                    $globStart . '*type:data.sql. ' .
                    "\n" . ' Files founded: ' . "\n" . implode("\n", $dataSqlFile),
                    1500722095323);
            }
            // Drop all tables.
            runLocally(sprintf(
                'export MYSQL_PWD=%s && %s -h%s -P%s -u%s -D%s --add-drop-table --no-data | ' .
                'grep -e \'^DROP \| FOREIGN_KEY_CHECKS\' | %s -h%s -P%s -u%s -D%s',
                escapeshellarg($databaseConfig['password']),
                get('local/bin/mysqldump'),
                escapeshellarg($databaseConfig['host']),
                escapeshellarg((isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306),
                escapeshellarg($databaseConfig['user']),
                escapeshellarg($databaseConfig['dbname']),
                get('local/bin/mysql'),
                escapeshellarg($databaseConfig['host']),
                escapeshellarg((isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306),
                escapeshellarg($databaseConfig['user']),
                escapeshellarg($databaseConfig['dbname'])
            ), 0);
            // Import dump with database structure.
            runLocally(sprintf(
                'export MYSQL_PWD=%s && %s --default-character-set=utf8 -h%s -P%s -u%s -D%s -e "SOURCE %s" ',
                escapeshellarg($databaseConfig['password']),
                get('local/bin/mysql'),
                escapeshellarg($databaseConfig['host']),
                escapeshellarg((isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306),
                escapeshellarg($databaseConfig['user']),
                escapeshellarg($databaseConfig['dbname']),
                escapeshellarg($structureSqlFile[0])
            ), 0);
            // Import dump with data.
            runLocally(sprintf(
                'export MYSQL_PWD=%s && %s --default-character-set=utf8 -h%s -P%s -u%s -D%s -e "SOURCE %s" ',
                escapeshellarg($databaseConfig['password']),
                get('local/bin/mysql'),
                escapeshellarg($databaseConfig['host']),
                escapeshellarg((isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306),
                escapeshellarg($databaseConfig['user']),
                escapeshellarg($databaseConfig['dbname']),
                escapeshellarg($dataSqlFile[0])
            ), 0);
            $postSqlInCollected = [];
            if (isset($databaseConfig['post_sql_in_markers'])) {
                // Prepare some markers to use in post_sql_in_markers:
                $markersArray = [];
                if (is_array(get('public_urls'))) {
                    $publicUrlCollected = [];
                    foreach (get('public_urls') as $publicUrl) {
                        if (parse_url($publicUrl, PHP_URL_SCHEME) && parse_url($publicUrl, PHP_URL_HOST)) {
                            $publicUrlCollected[] = parse_url($publicUrl, PHP_URL_HOST);
                        } else {
                            throw new \RuntimeException('The configuration setting "public_urls" should have full url like 
                        "https://www.example.com" but the value is only "' . $publicUrl . '"', 1491384103020);
                        }
                    }
                    $markersArray['{{domainsSeparatedByComma}}'] = implode(
                        ',',
                        array_map('mysql_real_escape_string', $publicUrlCollected)
                    );
                    $markersArray['{{firstDomainWithScheme}}'] = get('public_urls')[0];
                    $markersArray['{{firstDomainWithSchemeAndEndingSlash}}'] = rtrim(get('public_urls')[0], '/') . '/';
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
                $importSqlFile = $fileUtility->normalizeFolder($currentInstanceDatabaseStoragePath) . $dumpCode . '.sql';
                file_put_contents($importSqlFile, implode(' ', $postSqlInCollected));
                runLocally(sprintf(
                    'export MYSQL_PWD=%s && %s --default-character-set=utf8 -h%s -P%s -u%s -D%s -e "SOURCE %s" ',
                    escapeshellarg($databaseConfig['password']),
                    get('local/bin/mysql'),
                    escapeshellarg($databaseConfig['host']),
                    escapeshellarg((isset($databaseConfig['port']) && $databaseConfig['port']) ? $databaseConfig['port'] : 3306),
                    escapeshellarg($databaseConfig['user']),
                    escapeshellarg($databaseConfig['dbname']),
                    escapeshellarg($importSqlFile)
                ), 0);
                unlink($importSqlFile);
            }
            if (isset($databaseConfig['post_command']) && is_array($databaseConfig['post_command'])) {
                foreach ($databaseConfig['post_command'] as $postCommand) {
                    runLocally($postCommand . ' --dumpcode=' . $dumpCode, 0);
                }
            }
        }
    } else {
        if (test('[ -L {{deploy_path}}/release ]')) {
            run("cd {{deploy_path}}/release && {{bin/php}} {{bin/deployer}} db:import --dumpcode=" . $dumpCode);
        } else {
            run("cd {{deploy_path}}/current && {{bin/php}} {{bin/deployer}} db:import --dumpcode=" . $dumpCode);
        }
    }
})->desc('Import the database with "dumpcode" from current database dumps storage to database.');
