<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use function Deployer\runLocally;

/**
 * Class FileUtility
 * @package SourceBroker\DeployerExtendedDatabase\Utility
 */
class FileUtility
{
    /**
     * @param $filename
     * @return string
     */
    public function normalizeFilename($filename)
    {
        return preg_replace('/^[^a-zA-Z0-9_]+$/', '', $filename);
    }

    /**
     * @param $folder
     * @return string
     */
    public function normalizeFolder($folder)
    {
        return rtrim($folder, '/') . '/';
    }

    /**
     * @param $name
     * @return string
     */
    public function locateLocalBinaryPath($name)
    {
        $nameEscaped = escapeshellarg($name);
        // Try `command`, should cover all Bourne-like shells
        // Try `which`, should cover most other cases
        // Fallback to `type` command, if the rest fails
        $path = runLocally("command -v $nameEscaped || which $nameEscaped || type -p $nameEscaped");
        if ($path) {
            // Deal with issue when `type -p` outputs something like `type -ap` in some implementations
            return trim(str_replace("$name is", "", $path));
        }
        throw new \RuntimeException("Can't locate [$nameEscaped] on instance '" . get('default_stage') . "' - neither of [command|which|type] commands are available");
    }
}
