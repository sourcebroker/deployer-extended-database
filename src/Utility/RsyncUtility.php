<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

use Deployer\Task\Context;

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
    public function getSshOptions(Context $taskContext)
    {
        $sshOptions = [];
        $serverConfiguration = $taskContext->getServer()->getConfiguration();
        $sshOptions[] = $serverConfiguration->getPort() ? ' -p' . $serverConfiguration->getPort() : null;
        $sshOptions[] = $serverConfiguration->getPrivateKey() ? ' -i ' . $serverConfiguration->getPrivateKey() : null;
        return trim(implode(' ', $sshOptions));
    }

    public function getServerWithDbStoragePath(Context $taskContext)
    {
        $serverEnvironment = $taskContext->getEnvironment();
        $serverConfiguration = $taskContext->getServer()->getConfiguration();
        $serverWithPath =
            ($serverConfiguration->getUser() ? $serverConfiguration->getUser() . '@' : '') .
            $serverConfiguration->getHost() .
            trim($serverEnvironment->get('db_storage_path'), '/') . '/';
        return $serverWithPath;
    }
}
