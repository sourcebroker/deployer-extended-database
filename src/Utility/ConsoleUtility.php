<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use function Deployer\input;
use function Deployer\output;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ConsoleUtility
 *
 * @package SourceBroker\DeployerExtendedDatabase\Utility
 */
class ConsoleUtility
{
    /**
     * Returns OutputInterface verbosity as parameter that can be used in cli command
     *
     * @return string
     */
    public function getVerbosityAsParameter()
    {
        switch (output()->getVerbosity()) {
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

    /**
     * Check if option is present and return it. If not throw exception.
     *
     * @param $optionToFind
     * @param bool $required
     * @return string
     */
    public function getOption($optionToFind, $required = false)
    {
        $optionReturnValue = null;
        if (!empty(input()->getOption('options'))) {
            $options = explode(',', input()->getOption('options'));
            if (is_array($options)) {
                foreach ($options as $option) {
                    $optionParts = explode(':', $option);
                    if (!empty($optionParts[1])) {
                        $optionValue = $optionParts[1];
                    }
                    if ($optionToFind === $optionParts[0]) {
                        if (!empty($optionValue)) {
                            $optionReturnValue = $optionValue;
                        } else {
                            $optionReturnValue = true;
                        }
                    }
                }
            }
        }
        if ($required && $optionReturnValue === null) {
            throw new \InvalidArgumentException('No `--options=' . $optionToFind . ':value` set.', 1458937128560);
        }
        return $optionReturnValue;
    }

    public function getOptionsForCliUsage(array $optionsToSet)
    {
        $getOptionsForCliUsage = '';
        $getOptionsForCliUsageArray = [];
        foreach ($optionsToSet as $optionToSetKey => $optionToSetValue) {
            if ($optionToSetValue === true) {
                $optionToSetValue = 'true';
            } elseif ($optionToSetValue === false) {
                $optionToSetValue = 'false';
            }
            $getOptionsForCliUsageArray[] = $optionToSetKey . ':' . $optionToSetValue;
        }
        return $getOptionsForCliUsage . (!empty($getOptionsForCliUsageArray) ? '--options=' . implode(
            ',',
            $getOptionsForCliUsageArray
        ) : '');
    }
}
