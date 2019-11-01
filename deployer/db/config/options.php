<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

option('db-dumpcode', null, InputOption::VALUE_OPTIONAL, 'Database dump code');
option('db-options', null, InputOption::VALUE_OPTIONAL, 'Database commands options');
option('db-target', null, InputOption::VALUE_OPTIONAL, 'Target for task which needs source instance and target instance.');
