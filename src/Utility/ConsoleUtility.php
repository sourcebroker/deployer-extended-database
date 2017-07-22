<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use function Couchbase\defaultDecoder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ConsoleUtility
 * @package SourceBroker\DeployerExtendedDatabase\Utility
 */
class ConsoleUtility
{
    public function getVerbosity(OutputInterface $output)
    {
        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_DEBUG:
                $verbosity = ' -vvv';
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $verbosity = ' -vv';
                break;
            case OutputInterface::VERBOSITY_VERBOSE:
                $verbosity = ' -v';
                break;
            case OutputInterface::VERBOSITY_QUIET:
                $verbosity = ' -q';
                break;
            case OutputInterface::VERBOSITY_NORMAL:
            default:
                $verbosity = '';
        }
        return $verbosity;
    }
}
