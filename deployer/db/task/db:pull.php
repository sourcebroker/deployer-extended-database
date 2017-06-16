<?php

namespace Deployer;

task('db:pull', function () {
    if (null === input()->getArgument('stage')) {
        throw new \RuntimeException("The target instance is required for db:pull command.");
    }

    $sourceInstance = get('server')['name'];

    $command = parse("cd {{deploy_path}}/current && {{bin/php}} {{bin/deployer}} -q db:export");
    $databaseDumpResult = run($command);
    $dbExportOnTargetInstanceResponse = json_decode(trim($databaseDumpResult->toString()), true);
    if ($dbExportOnTargetInstanceResponse == null) {
        throw new \RuntimeException(
            "db:export failed on " . $sourceInstance . ". The database dumpcode is null. Try to call: \n" .
            $command . "\n" .
            "on " . $sourceInstance . " instance. \n" .
            "Export task returned: " . $databaseDumpResult->toString() . "\n" .
            "One of the reason can be PHP notices or warnings added to output."
        );
    }

    if ($dbExportOnTargetInstanceResponse !== null && isset($dbExportOnTargetInstanceResponse['dumpCode'])) {
        $dumpCode = $dbExportOnTargetInstanceResponse['dumpCode'];
        runLocally("{{local/bin/deployer}} db:download $sourceInstance --dumpcode=$dumpCode", 0);
        runLocally("{{local/bin/deployer}} db:process --dumpcode=$dumpCode", 0);
        runLocally("{{local/bin/deployer}} db:import --dumpcode=$dumpCode", 0);
    } else {
        throw new \RuntimeException('db:export did not returned dumpcode in json! 
        Check if json response is clean. Sometimes there are PHP Warnings before 
        json response which are breaking jsone_decode.');
    }
})->desc('Synchronize database from remote instance to local instance.');
