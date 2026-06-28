<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use SourceBroker\DeployerInstance\Configuration;

class RsyncUtility
{
    public function getSshOptions(string $targetStageName): string
    {
        $host = Configuration::getHost($targetStageName);
        $connectionOptions = method_exists($host, 'connectionOptionsString')
            ? $host->connectionOptionsString()
            : implode(' ', array_map('escapeshellarg', $host->connectionOptions()));
        if ($connectionOptions !== '') {
            return '-e "ssh ' . $connectionOptions . '"';
        }
        return '';
    }

    public function getHostWithDbStoragePath(string $targetStageName): string
    {
        $host = Configuration::getHost($targetStageName);
        return
            ($host->getRemoteUser() ? $host->getRemoteUser() . '@' : '') .
            $host->getHostname() .
            ':' . rtrim(Configuration::getHost($targetStageName)->get('db_storage_path'), '/') . '/';
    }
}
