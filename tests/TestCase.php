<?php


namespace Sopamo\ClusterCache\Tests;

use Sopamo\ClusterCache\ClusterCacheServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // additional setup
    }

    protected function getPackageProviders($app): array
    {
        return [
            ClusterCacheServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        // import the CreatePostsTable class from the migration
        include_once __DIR__ . '/../database/migrations/create_cache_entries_table.php.stub';
        include_once __DIR__ . '/../database/migrations/create_hosts_table.php.stub';

        // run the up() method of that migration class
        (new \CreateCacheEntriesTable)->up();
        (new \CreateHostsTable)->up();
    }
}