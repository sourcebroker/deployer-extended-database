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
        $serverEnvironment = Configuration::getHost($targetStageName)->getConfig();
        $serverConfiguration = Configuration::getHost($targetStageName);
        $serverWithPath =
            ($serverConfiguration->getUser() ? $serverConfiguration->getUser() . '@' : '') .
            $serverConfiguration->getRealHostname() .
            ':/' . trim($serverEnvironment->get('db_storage_path'), '/') . '/';
        return $serverWithPath;
    }
}
