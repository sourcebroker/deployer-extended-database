<?php

namespace Deployer;

task('db:pull', function () {
    if (null === input()->getArgument('stage')) {
        throw new \RuntimeException("The target instance is required for db:pull command.");
    }
    $sourceInstance = get('server')['name'];
    $dumpCode = md5(microtime(true) . rand(0, 10000));

    runLocally("{{local/bin/deployer}} db:export $sourceInstance --dumpcode=$dumpCode");
    runLocally("{{local/bin/deployer}} db:download $sourceInstance --dumpcode=$dumpCode", 0);
    runLocally("{{local/bin/deployer}} db:process --dumpcode=$dumpCode", 0);
    runLocally("{{local/bin/deployer}} db:import --dumpcode=$dumpCode", 0);
})->desc('Synchronize database from remote instance to current instance.');
