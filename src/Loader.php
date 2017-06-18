<?php

namespace SourceBroker\DeployerExtendedDatabase;

class Loader
{
    public function __construct()
    {
        \SourceBroker\DeployerExtendedDatabase\Utility\FileUtility::requireFilesFromDirectoryReqursively(
            dirname((new \ReflectionClass('\SourceBroker\DeployerExtendedDatabase\Loader'))->getFileName()) . '/../deployer/'
        );
    }
}
