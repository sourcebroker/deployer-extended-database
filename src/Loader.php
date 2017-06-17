<?php

namespace SourceBroker\DeployerExtendedDatabase;

class Loader
{
    public function init()
    {
        if (is_dir(getcwd()) && file_exists(getcwd() . '/deploy.php')) {
            \Deployer\set('current_dir', getcwd());
        } else {
            throw new \RuntimeException('Can not set "current_dir" var. Are you in folder with deploy.php file?');
        }
        $recipePath = dirname((new \ReflectionClass('\SourceBroker\DeployerExtendedDatabase\Loader'))->getFileName()) . '/../deployer/';
        \SourceBroker\DeployerExtendedDatabase\Utility\FileUtility::requireFilesFromDirectoryReqursively($recipePath);
    }
}