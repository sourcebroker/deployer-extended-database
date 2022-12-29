<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use SourceBroker\DeployerInstance\Configuration;

class RsyncUtility
{
    public function getSshOptions(string $targetStageName): string
    {
        $connectionOptions = Configuration::getHost($targetStageName)->connectionOptionsString();
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
            ':/' . trim(Configuration::getHost($targetStageName)->get('db_storage_path'), '/') . '/';
    }
}
