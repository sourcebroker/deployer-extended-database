<?php

namespace SourceBroker\DeployerExtendedDatabase;

class Loader
{
    public function __construct()
    {
        $recipePath = dirname((new \ReflectionClass('\SourceBroker\DeployerExtendedDatabase\Loader'))->getFileName()) . '/../deployer/';
        \SourceBroker\DeployerExtendedDatabase\Utility\FileUtility::requireFilesFromDirectoryReqursively($recipePath);
    }
}