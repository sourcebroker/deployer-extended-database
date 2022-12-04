<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use SourceBroker\DeployerInstance\Configuration;
use Deployer\Exception\GracefulShutdownException;

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
    public function getSshOptions($targetStageName): string
    {
        $host = Configuration::getHost($targetStageName);
        if (!empty($host->connectionOptionsString())) {
            return '-e ' . escapeshellarg('ssh ' . $host->connectionOptionsString());
        } else {
            return '';
        }
    }

    /**
     * @param $targetStageName
     * @return string
     */
    public function getHostWithDbStoragePath($targetStageName): string
    {
        $host = Configuration::getHost($targetStageName);
        return
            ($host->getRemoteUser() ? $host->getRemoteUser() . '@' : '') .
            $host->getHostname() .
            ':/' . trim(Configuration::getHost($targetStageName)->get('db_storage_path'), '/') . '/';
    }
}
