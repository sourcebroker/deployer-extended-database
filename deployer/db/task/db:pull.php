<?php

namespace Deployer;

use Symfony\Component\Console\Output\OutputInterface;

task('db:pull', function () {
    if (null === input()->getArgument('stage')) {
        throw new \RuntimeException("The target instance is required for db:pull command.");
    }
    $sourceInstance = get('server')['name'];
    $dumpCode = md5(microtime(true) . rand(0, 10000));
    $verbosity = '';
    if (output()->getVerbosity() == OutputInterface::VERBOSITY_DEBUG) {
        $verbosity = ' -vvv';
    }
    if (output()->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
        $verbosity = ' -vv';
    }
    if (output()->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
        $verbosity = ' -v';
    }

    runLocally("{{local/bin/deployer}} db:export $sourceInstance --dumpcode=$dumpCode $verbosity");
    runLocally("{{local/bin/deployer}} db:download $sourceInstance --dumpcode=$dumpCode $verbosity", 0);
    runLocally("{{local/bin/deployer}} db:process --dumpcode=$dumpCode $verbosity", 0);
    runLocally("{{local/bin/deployer}} db:import --dumpcode=$dumpCode $verbosity", 0);
})->desc('Synchronize database from remote instance to current instance.');
