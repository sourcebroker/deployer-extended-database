<?php

namespace SourceBroker\DeployerExtendedDatabase\Driver;

use Symfony\Component\Dotenv\Dotenv;

/**
 * Configuration reader for database data stored in .env file.
 *
 * Class EnvDriver
 * @package SourceBroker\DeployerExtendedDatabase\Driver
 */
class EnvDriver
{
    /**
     * @param string $prefix
     * @param null $absolutePath
     * @return array
     * @throws \Exception
     * @internal param null $params
     */
    public function getDatabaseConfig($prefix = '', $absolutePath = null)
    {
        $absolutePath = null === $absolutePath ? getcwd() : $absolutePath;
        $absolutePath = rtrim($absolutePath, DIRECTORY_SEPARATOR);
        if (file_exists($absolutePath . '/.env')) {
            (new Dotenv())->load($absolutePath . '/.env');
            foreach (['DATABASE_HOST', 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASSWORD'] as $requiredEnv) {
                if (false === getenv($prefix . $requiredEnv)) {
                    throw new \Exception('Missing ' . $prefix . $requiredEnv . ' in .env file.');
                }
            }
            return [
                'host' => getenv($prefix . 'DATABASE_HOST'),
                'port' => getenv($prefix . 'DATABASE_PORT') ? getenv($prefix . 'DATABASE_PORT') : 3306,
                'dbname' => getenv($prefix . 'DATABASE_NAME'),
                'user' => getenv($prefix . 'DATABASE_USER'),
                'password' => getenv($prefix . 'DATABASE_PASSWORD')
            ];
        } else {
            throw new \Exception('Missing file "' . getcwd() . '/.env');
        }
    }
}
