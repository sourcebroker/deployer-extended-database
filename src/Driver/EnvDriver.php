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
            // required
            'host' => $this->getenv($prefix . 'DATABASE_HOST'),
            'port' => $this->getenv($prefix . 'DATABASE_PORT') ?: 3306,
            'dbname' => $this->getenv($prefix . 'DATABASE_NAME'),
            'user' => $this->getenv($prefix . 'DATABASE_USER'),
            'password' => $this->getenv($prefix . 'DATABASE_PASSWORD'),
            // SSL
            'ssl_key' => $this->getenv($prefix . 'DATABASE_SSL_KEY'),
            'ssl_cert' => $this->getenv($prefix . 'DATABASE_SSL_CERT'),
            'ssl_ca' => $this->getenv($prefix . 'DATABASE_SSL_CA'),
            'ssl_capath' => $this->getenv($prefix . 'DATABASE_SSL_CAPATH'),
            'ssl_cipher' => $this->getenv($prefix . 'DATABASE_SSL_CIPHER'),
            // options
            'options' => [
                MYSQLI_OPT_CONNECT_TIMEOUT => $this->getenv($prefix . 'DATABASE_OPTION_MYSQLI_OPT_CONNECT_TIMEOUT'),
                MYSQLI_OPT_READ_TIMEOUT => $this->getenv($prefix . 'DATABASE_OPTION_MYSQLI_OPT_READ_TIMEOUT'),
                MYSQLI_OPT_LOCAL_INFILE => $this->getenv($prefix . 'DATABASE_OPTION_MYSQLI_OPT_LOCAL_INFILE'),
                MYSQLI_INIT_COMMAND => $this->getenv($prefix . 'DATABASE_OPTION_MYSQLI_INIT_COMMAND'),
                MYSQLI_SET_CHARSET_NAME => $this->getenv($prefix . 'DATABASE_OPTION_MYSQLI_SET_CHARSET_NAME'),
                MYSQLI_READ_DEFAULT_GROUP => $this->getenv($prefix . 'DATABASE_OPTION_MYSQLI_READ_DEFAULT_GROUP'),
                MYSQLI_SERVER_PUBLIC_KEY => $this->getenv($prefix . 'DATABASE_OPTION_MYSQLI_SERVER_PUBLIC_KEY'),
                MYSQLI_OPT_NET_CMD_BUFFER_SIZE => $this->getenv($prefix . 'DATABASE_OPTION_MYSQLI_OPT_NET_CMD_BUFFER_SIZE'),
                MYSQLI_OPT_NET_READ_BUFFER_SIZE => $this->getenv($prefix . 'DATABASE_OPTION_MYSQLI_OPT_NET_READ_BUFFER_SIZE'),
                MYSQLI_OPT_INT_AND_FLOAT_NATIVE => $this->getenv($prefix . 'DATABASE_OPTION_MYSQLI_OPT_INT_AND_FLOAT_NATIVE'),
                MYSQLI_OPT_SSL_VERIFY_SERVER_CERT => $this->getenv($prefix . 'DATABASE_OPTION_MYSQLI_OPT_SSL_VERIFY_SERVER_CERT'),
            ],
        ];
    }

    private function getenv($env)
    {
        return $_ENV[$env] ?? null;
    }
}
