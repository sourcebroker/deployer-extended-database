<?php

namespace SourceBroker\DeployerExtendedDatabase\Driver;

use RuntimeException;
use SourceBroker\DeployerInstance\Env;

/**
 * Configuration reader for database data stored in .env file.
 *
 * Class EnvDriver
 * @package SourceBroker\DeployerExtendedDatabase\Driver
 */
class EnvDriver
{
    /**
     * @param string|null $prefix
     * @param string|null $absolutePath
     * @return array
     * @throws \Exception
     */
    public function getDatabaseConfig(string $prefix = null, string $absolutePath = null): array
    {
        $envFilePath = rtrim($absolutePath ?? getcwd(), DIRECTORY_SEPARATOR) . '/.env';
        (new Env)->load($envFilePath);
        foreach (['DATABASE_HOST', 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASSWORD'] as $requiredEnv) {
            if (false === $this->getenv($prefix . $requiredEnv)) {
                throw new RuntimeException('Missing ' . $prefix . $requiredEnv . ' in ' . $envFilePath . ' file.');
            }
        }
        return [
            'host' => $this->getenv($prefix . 'DATABASE_HOST'),
            'port' => $this->getenv($prefix . 'DATABASE_PORT') ?: 3306,
            'dbname' => $this->getenv($prefix . 'DATABASE_NAME'),
            'user' => $this->getenv($prefix . 'DATABASE_USER'),
            'password' => $this->getenv($prefix . 'DATABASE_PASSWORD')
        ];
    }

    private function getenv($env)
    {
        return $_ENV[$env] ?? null;
    }
}
