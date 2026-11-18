<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $database = $_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION');
        $databaseName = $_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE');
        $databaseUrl = $_ENV['DB_URL'] ?? $_SERVER['DB_URL'] ?? getenv('DB_URL');
        $environment = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV');
        $configCache = $_ENV['APP_CONFIG_CACHE'] ?? $_SERVER['APP_CONFIG_CACHE'] ?? getenv('APP_CONFIG_CACHE');

        if ($database !== 'sqlite'
            || $databaseName !== ':memory:'
            || ($databaseUrl !== false && $databaseUrl !== '')
            || $environment !== 'testing'
            || $configCache !== 'bootstrap/cache/config.testing.php'
            || is_file(dirname(__DIR__) . '/bootstrap/cache/config.testing.php')) {
            throw new \RuntimeException('Tests require isolated SQLite in-memory configuration.');
        }

        parent::setUp();
    }
}
