<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

/**
 * Class InstanceUtility
 * @package SourceBroker\DeployerExtendedDatabase\Utility
 */
class InstanceUtility
{
    public static function getCurrentInstance()
    {
        if (getenv("INSTANCE") === false && getenv("INSTANCE_DEPLOYER") === false) {
            $configDir = getcwd();
            if (file_exists($configDir . '/.env')) {
                $dotenv = new \Dotenv\Dotenv($configDir);
                $dotenv->load();
            } else {
                throw new \Exception('Missing file "' . $configDir . '/.env"');
            }
        }
        if (getenv("INSTANCE") === false && getenv("INSTANCE_DEPLOYER") === false) {
            throw new \Exception('Neither env var INSTANCE or INSTANCE_DEPLOYER is set. Please
            set one of them with the name of INSTANCE which should coresspond to server() name."');
        }
        return getenv('INSTANCE') === false ? getenv('INSTANCE_DEPLOYER') : getenv('INSTANCE');
    }
}
