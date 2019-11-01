<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

option('dumpcode', null, InputOption::VALUE_OPTIONAL, 'Database dump code');
option('dboptions', null, InputOption::VALUE_OPTIONAL, 'Database commands options');
option('dbtarget', null, InputOption::VALUE_OPTIONAL, 'Target for task which needs source instance and target instance.');
