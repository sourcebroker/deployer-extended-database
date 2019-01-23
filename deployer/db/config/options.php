<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

option('dumpcode', null, InputOption::VALUE_OPTIONAL, 'Database dump code');
option('dboptions', null, InputOption::VALUE_OPTIONAL, 'Database commands options');
