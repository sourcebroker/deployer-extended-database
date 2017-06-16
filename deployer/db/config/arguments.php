<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputArgument;

argument('targetStage', InputArgument::OPTIONAL, 'Run tasks only on this server or group of servers.');
