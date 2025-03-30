<?php

namespace SourceBroker\DeployerExtendedDatabase\Driver;

use RuntimeException;
use SourceBroker\DeployerInstance\Env;

class EnvDriver
{
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
            // required
            'host' => $this->getenv($prefix . 'DATABASE_HOST'),
            'port' => $this->getenv($prefix . 'DATABASE_PORT') ?: 3306,
            'dbname' => $this->getenv($prefix . 'DATABASE_NAME'),
            'user' => $this->getenv($prefix . 'DATABASE_USER'),
            'password' => $this->getenv($prefix . 'DATABASE_PASSWORD'),
            // flags
            'flags' => $this->getenv($prefix . 'DATABASE_FLAGS'),
            // SSL
            'ssl_key' => $this->getenv($prefix . 'DATABASE_SSL_KEY'),
            'ssl_cert' => $this->getenv($prefix . 'DATABASE_SSL_CERT'),
            'ssl_ca' => $this->getenv($prefix . 'DATABASE_SSL_CA'),
            'ssl_capath' => $this->getenv($prefix . 'DATABASE_SSL_CAPATH'),
            'ssl_cipher' => $this->getenv($prefix . 'DATABASE_SSL_CIPHER'),
        ];
    }

    private function getenv($env)
    {
        return $_ENV[$env] ?? null;
    }
}
