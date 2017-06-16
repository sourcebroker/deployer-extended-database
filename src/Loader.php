<?php

namespace SourceBroker\DeployerExtendedDatabase;

class Loader
{
    public function __construct()
    {
        // Local call of deployer can be not standard. For example someone could have "dep3" and "dep4" symlinks and call
        // "dep3 deploy live". He could expect then that if we will use deployer call inside task we will use then "dep3" and not "dep"
        // so we store actual way of calling deployer into "local/bin/deployer" var to use it whenever we call local deployer again in tasks.
        if ($_SERVER['_'] == $_SERVER['PHP_SELF']) {
            \Deployer\set('local/bin/deployer', $_SERVER['_']);
        } else {
            \Deployer\set('local/bin/deployer', $_SERVER['_'] . $_SERVER['PHP_SELF']);
        }
        if (is_dir(getcwd()) && file_exists(getcwd() . '/deploy.php')) {
            \Deployer\set('current_dir', getcwd());
        } else {
            throw new \RuntimeException('Can not set "current_dir" var. Are you in folder with deploy.php file?');
        }
        $recipePath = dirname((new \ReflectionClass('\SourceBroker\DeployerExtendedDatabase\Loader'))->getFileName()) . '/../deployer/';
        \SourceBroker\DeployerExtendedDatabase\Utility\FileUtility::requireFilesFromDirectoryReqursively($recipePath);
    }
}