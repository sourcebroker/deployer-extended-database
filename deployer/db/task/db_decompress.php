<?php

namespace Deployer;

use SourceBroker\DeployerExtendedDatabase\Utility\ConsoleUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\DatabaseUtility;
use SourceBroker\DeployerExtendedDatabase\Utility\OptionUtility;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * @see https://github.com/sourcebroker/deployer-extended-database#db-decompress
 */
task('db:decompress', function () {
    $optionUtility = new OptionUtility(input()->getOption('options'));
    $dumpCode = $optionUtility->getOption('dumpcode', true);
    if (get('is_argument_host_the_same_as_local_host')) {
        $decompressedDumpFile = (new DatabaseUtility())->getDumpFile(
            get('db_storage_path_local'), ['dumpcode' => $dumpCode], ['sql']
        );
        if ($decompressedDumpFile !== null) {
            writeln(
                'The .sql file with the given dumpCode already exists, skipping decompression.',
                OutputInterface::VERBOSITY_VERBOSE
            );
            return;
        }

        $markersArray = [];
        $markersArray['{{databaseStorageAbsolutePath}}'] = get('db_storage_path_local');
        $markersArray['{{dumpcode}}'] = $dumpCode;
        if (get('db_decompress_command', false) !== false) {
            foreach (get('db_decompress_command') as $dbProcessCommand) {
                runLocally(str_replace(
                    array_keys($markersArray),
                    $markersArray,
                    $dbProcessCommand
                ));
            }
        }
    } else {
        $params = [
            get('argument_host'),
            (new ConsoleUtility())->getVerbosityAsParameter(),
            $optionUtility->getOptionsString(),
        ];
        run('cd {{release_or_current_path}} && {{bin/php}} {{bin/deployer}} db:decompress ' . implode(' ', $params));
    }
})->desc('Compress dumps with given dumpcode');
