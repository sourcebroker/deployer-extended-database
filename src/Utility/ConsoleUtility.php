<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use function Deployer\input;
use function Deployer\output;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleUtility
{
    public function getVerbosityAsParameter(): string
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
     * @return bool|mixed|string|null
     */
    public function getOption(string $optionToFind, bool $required = false)
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

    public function getOptionsForCliUsage(array $optionsToSet): string
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

    public function formattingSubtaskTree(string $content, string $type = ''): string
    {
        return match ($type) {
            'end' => '  └──╸' . $content,
            default => '  ├──╸' . $content,
        };
    }

    public function formattingTaskOutputHeader(string $output, bool $tab = true): string
    {
        $content = "\033[35;1m" . $output . "\033[0m";
        return $tab ? $this->formattingTaskOutputTab($content) : $content;
    }

    public function formattingTaskOutputContent(string $output, bool $tab = true): string
    {
        $content = "\033[32;1m" . $output . "\033[0m";
        return $tab ? $this->formattingTaskOutputTab($content) : $content;
    }

    public function formattingTaskOutputTab($output): string
    {
        $outputLines = explode("\n", $output);
        $formattedLines = array_map(function($line) {
            $out = "\033[32;1m" . $line . "\033[0m";
            return preg_replace('/^/m', '  │   ', $out);
        }, $outputLines);
        return implode("\n", $formattedLines);
    }

}
