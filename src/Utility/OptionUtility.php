<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use Deployer\Exception\GracefulShutdownException;
use function Deployer\get;

class OptionUtility
{
    public const AVAILABLE_OPTIONS = [
        'dumpcode',
        'tags',
        'target',
        'fromLocalStorage',
        'exportTaskAddIgnoreTablesToStructureDump',
        'importTaskDoNotDropAllTablesBeforeImport',
    ];

    public const ARRAY_OPTIONS_IMPLODE_CHAR = '+';

    public const ARRAY_OPTIONS = [
        'tags',
    ];

    private $options = [];

    public function __construct(?string $optionsString = '')
    {
        if (!empty($optionsString)) {
            $this->parseOptionsString($optionsString);
        }
    }

    private function parseOptionsString(string $optionsString): void
    {
        $options = explode(',', $optionsString);
        foreach ($options as $option) {
            $optionParts = explode(':', $option);
            if (count($optionParts) === 2) {
                if (in_array($optionParts[0], self::ARRAY_OPTIONS, true)) {
                    $this->options[$optionParts[0]] = explode('+', $optionParts[1]);
                } else {
                    $this->options[$optionParts[0]] = $optionParts[1];
                }
            }
        }
    }

    public function getOption(string $optionName, bool $required = false)
    {
        $optionReturnValue = null;
        foreach ($this->options as $key => $value) {
            if (!in_array($key, self::AVAILABLE_OPTIONS, true)) {
                throw new GracefulShutdownException('Option `' . $key . '` is not available for --options=.',
                    1458937128562);
            }
            if ($optionName === $key) {
                $pregMatchRequired = get('db_pregmatch_' . $optionName, '');
                if ($pregMatchRequired !== '' && !empty($value) && !preg_match($pregMatchRequired, $value)) {
                    throw new GracefulShutdownException('Value of option `' . $optionName . '` does not match the required pattern: ' . $pregMatchRequired,
                        1458937128561);
                }
                if (!empty($value)) {
                    $optionReturnValue = $value;
                } else {
                    $optionReturnValue = null;
                }
            }
        }

        if ($required && $optionReturnValue === null) {
            throw new GracefulShutdownException('No `--options=' . $optionName . ':value` set.', 1458937128560);
        }

        return in_array($optionName, self::ARRAY_OPTIONS, true) ? (array)$optionReturnValue : $optionReturnValue;
    }

    public function setOption(string $optionName, $optionValue): void
    {
        if (in_array($optionName, self::ARRAY_OPTIONS, true)) {
            if (is_array($optionValue)) {
                $this->options[$optionName] = $optionValue;
            } else {
                $this->options[$optionName][] = $optionValue;
            }
        } else {
            $this->options[$optionName] = $optionValue;
        }
    }

    public function removeOption(string $optionName): void
    {
        if (isset($this->options[$optionName])) {
            unset($this->options[$optionName]);
        }
    }

    public function getOptionsString(): string
    {
        $optionsArray = [];
        foreach ($this->options as $key => $value) {
            if ($value === true) {
                $value = 'true';
            } elseif ($value === false) {
                $value = 'false';
            }
            if (in_array($key, self::ARRAY_OPTIONS, true) && is_array($value)) {
                $value = implode('+', $value);
            }
            $optionsArray[] = $key . ':' . $value;
        }
        return '--options=' . implode(',', $optionsArray);
    }
}
