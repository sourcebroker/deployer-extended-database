<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use function Couchbase\defaultDecoder;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

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

    public function optionRequired($requiredOption, InputInterface $input) {
        if (!empty($input->getOption($requiredOption))) {
            $requiredOptionValue = $input->getOption($requiredOption);
        } else {
            throw new \InvalidArgumentException('No --' . $requiredOption . ' option set.', 1458937128560);
        }
        return $requiredOptionValue;
    }
}
