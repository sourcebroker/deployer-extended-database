<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use SourceBroker\DeployerInstance\Configuration;

class RsyncUtility
{
    public function getSshOptions(string $targetStageName): string
    {
        $host = Configuration::getHost($targetStageName);
        if (!empty($host->connectionOptionsString())) {
            return '-e ' . escapeshellarg('ssh ' . $host->connectionOptionsString());
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
