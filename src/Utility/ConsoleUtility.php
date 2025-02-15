<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

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
