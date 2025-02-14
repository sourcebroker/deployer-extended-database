<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use function Deployer\input;
use function Deployer\output;
use function Deployer\get;
use Symfony\Component\Console\Output\OutputInterface;
use Deployer\Exception\GracefulShutdownException;

class ConsoleUtility
{
    public const AVAILABLE_OPTIONS = [
        'dumpcode',
        'target',
        'fromLocalStorage',
        'exportTaskAddIgnoreTablesToStructureDump',
        'importTaskDoNotDropAllTablesBeforeImport',
    ];

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
                    if (!in_array($optionParts[0], self::AVAILABLE_OPTIONS, true)) {
                        throw new GracefulShutdownException('Option `' . $optionParts[0] . '` is not available for --options=.', 1458937128562);
                    }
                    if (!empty($optionParts[1])) {
                        $optionValue = $optionParts[1];
                    }
                    if ($optionToFind === $optionParts[0]) {
                        $pregMatchRequired = get('db_pregmatch_' . $optionToFind, '');
                        if ($pregMatchRequired !== '' && !empty($optionValue)
                            && !preg_match($pregMatchRequired, $optionValue)) {
                            throw new GracefulShutdownException('Value of option `' . $optionToFind . '` does not match the required pattern: ' . $pregMatchRequired,
                                1458937128561);
                        }
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
            throw new GracefulShutdownException('No `--options=' . $optionToFind . ':value` set.', 1458937128560);
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

    public function formattingSubtaskTree(string $content): string
    {
        return '  ├──╸' . $content;
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
        $formattedLines = array_map(function ($line) {
            $out = "\033[32;1m" . $line . "\033[0m";
            return preg_replace('/^/m', '  │   ', $out);
        }, $outputLines);
        return implode("\n", $formattedLines);
    }

    public function getDumpCode(): string
    {
        return md5(microtime(true) . random_int(0, 10000));
    }
}
