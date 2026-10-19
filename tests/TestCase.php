<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['db']->connection();
        // This runs before RefreshDatabase / DatabaseMigrations can drop tables.
        if (! $app->environment('testing') || $app->configurationIsCached()
            || $connection->getDriverName() !== 'mysql'
            || ! preg_match('/^[a-zA-Z0-9_]+_test$/D', $connection->getDatabaseName())) {
            throw new \RuntimeException('Tests require an isolated MySQL database ending in _test. Configure .env.testing (see .env.testing.example) and run php artisan config:clear.');
        }

        return $app;
    }
}
