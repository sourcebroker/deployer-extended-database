<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use function Deployer\runLocally;
use function Deployer\run;

class FileUtility
{
    public function normalizeFilename(string $filename): string
    {
        return preg_replace('/^[^a-zA-Z0-9_]+$/', '', $filename);
    }

    public function normalizeFolder(string $folder): string
    {
        return rtrim($folder, '/') . '/';
    }

    public function locateLocalBinaryPath(string $name): string
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
        throw new \RuntimeException("Can't locate [$nameEscaped] on instance '" . get('local_host') . "' - neither of [command|which|type] commands are available");
    }

    public function resolveHomeDirectory(string $path): string
    {
        if (str_starts_with($path, '~')) {
            $path = run('echo ${HOME:-${USERPROFILE}}' . escapeshellarg(substr($path, 1)));
        }
        return $path;
    }

    public function resolveHomeDirectoryLocal(string $path): string
    {
        if (str_starts_with($path, '~')) {
            $path = runLocally('echo ${HOME:-${USERPROFILE}}' . escapeshellarg(substr($path, 1)));
        }
        return $path;
    }
}
