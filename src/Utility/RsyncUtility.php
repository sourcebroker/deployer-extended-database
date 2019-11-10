<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use SourceBroker\DeployerInstance\Configuration;

/**
 * Class RsyncUtility
 *
 * @package SourceBroker\DeployerExtendedDatabase\Utility
 */
class RsyncUtility
{
    /**
     * @param $targetStageName
     * @return string
     */
    public function getSshOptions($targetStageName)
    {
        $sshOptions = [];
        $serverConfiguration = Configuration::getHost($targetStageName);
        $sshOptions[] = $serverConfiguration->getPort() ? ' -p' . $serverConfiguration->getPort() : null;
        $sshOptions[] = $serverConfiguration->getIdentityFile() ? ' -i ' . $serverConfiguration->getIdentityFile() : null;
        if (!empty(array_filter($sshOptions))) {
            return 'ssh ' . implode(' ', $sshOptions);
        } else {
            return '';
        }
    }

    public function getHostWithDbStoragePath($targetStageName)
    {
        $host = Configuration::getHost($targetStageName);
        return
            ($host->getUser() ? $host->getUser() . '@' : '') .
            $host->getRealHostname() .
            ':/' . trim(Configuration::getHost($targetStageName)->getConfig()->get('db_storage_path'), '/') . '/';
    }
}
