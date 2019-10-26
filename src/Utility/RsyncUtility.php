<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use Deployer\Task\Context;
use SourceBroker\DeployerInstance\Configuration;

/**
 * Class RsyncUtility
 *
 * @package SourceBroker\DeployerExtendedDatabase\Utility
 */
class RsyncUtility
{
    /**
     * @param Context $taskContext
     * @return string
     */
    public function getSshOptions($targetStageName)
    {
        $sshOptions = [];
        $serverConfiguration = Configuration::getServer($targetStageName)->getConfiguration();
        $sshOptions[] = $serverConfiguration->getPort() ? ' -p' . $serverConfiguration->getPort() : null;
        $sshOptions[] = $serverConfiguration->getPrivateKey() ? ' -i ' . $serverConfiguration->getPrivateKey() : null;
        if (!empty(array_filter($sshOptions))) {
            return 'ssh ' . implode(' ', $sshOptions);
        } else {
            return '';
        }
    }

    public function getHostWithDbStoragePath($targetStageName)
    {
        $serverEnvironment = Configuration::getEnvironment($targetStageName);
        $serverConfiguration = Configuration::getServer($targetStageName)->getConfiguration();
        $serverWithPath =
            ($serverConfiguration->getUser() ? $serverConfiguration->getUser() . '@' : '') .
            $serverConfiguration->getHost() .
            ':/' . trim($serverEnvironment->get('db_storage_path'), '/') . '/';
        return $serverWithPath;
    }
}
