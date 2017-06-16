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
    public static function normalizeFilename($filename)
    {
        return preg_replace("/[^a-zA-Z0-9_]+/", "", $filename);
    }

    /**
     * @param $folder
     * @return string
     */
    public static function normalizeFolder($folder)
    {
        return rtrim($folder, '/');
    }

    /**
     * @param $absolutePath string Absolute path to file
     * @return string
     */
    public static function getUniqueFileName($absolutePath)
    {
        $pathParts = pathinfo($absolutePath);
        $i = 0;
        while (file_exists($absolutePath) && $i < 100) {
            $absolutePath = $pathParts['dirname'] . '/' . $pathParts['filename'] . '_' . $i . '.' . $pathParts['extension'];
            $i++;
        }
        return $absolutePath;
    }

    /**
     * @param $absolutePath
     * @param null $excludePattern
     */
    public static function requireFilesFromDirectoryReqursively($absolutePath, $excludePattern = null)
    {
        if (is_dir($absolutePath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($absolutePath), \RecursiveIteratorIterator::SELF_FIRST);
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
