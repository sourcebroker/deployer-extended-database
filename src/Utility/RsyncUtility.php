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
        $host = Configuration::getHost($targetStageName);
        if (!empty($host->getSshArguments())) {
            return '-e ' . escapeshellarg('ssh ' . $host->getSshArguments()->getCliArguments());
        } else {
            return '';
        }
    }

    /**
     * @param $targetStageName
     * @return string
     */
    public function getHostWithDbStoragePath($targetStageName)
    {
        $host = Configuration::getHost($targetStageName);
        return
            ($host->getUser() ? $host->getUser() . '@' : '') .
            $host->getRealHostname() .
            ':/' . trim(Configuration::getHost($targetStageName)->getConfig()->get('db_storage_path'), '/') . '/';
    }
}
