<?php

namespace SourceBroker\DeployerExtendedDatabase;

use SourceBroker\DeployerExtendedDatabase\Utility\FileUtility;

class Loader
{
    public function __construct()
    {
        (new FileUtility())->requireFilesFromDirectoryReqursively(
            dirname((new \ReflectionClass('\SourceBroker\DeployerExtendedDatabase\Loader'))->getFileName()) . '/../deployer/'
        );
    }
}
