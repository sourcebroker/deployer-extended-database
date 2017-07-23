<?php

namespace SourceBroker\DeployerExtendedDatabase\Utility;

/**
 * Class FileUtility
 * @package SourceBroker\DeployerExtendedDatabase\Utility
 */
class FileUtility
{
    /**
     * @param $filename
     * @return mixed
     */
    public function normalizeFilename($filename)
    {
        return preg_replace('/[^a-zA-Z0-9_]+/', '', $filename);
    }

    /**
     * @param $folder
     * @return string
     */
    public function normalizeFolder($folder)
    {
        return rtrim($folder, '/') . '/';
    }


    /**
     * @param $absolutePath
     * @param null $excludePattern
     */
    public function requireFilesFromDirectoryReqursively($absolutePath, $excludePattern = null)
    {
        if (is_dir($absolutePath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolutePath),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $file) {
                /** @var $file \SplFileInfo */
                if ($file->isFile()) {
                    $excludeMatch = null;
                    if ($excludePattern !== null) {
                        $excludeMatch = preg_match($excludePattern, $file->getFilename());
                    }
                    if ($file->getExtension() == 'php' && $excludeMatch !== 1) {
                        require_once $file->getRealPath();
                    }
                }
            }
        }
    }
}
