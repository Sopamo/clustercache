<?php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\Models\Host;

class TestCase extends BaseTestCase {
    use CreatesApplication;

    const HOST_CLUSTERCACHE1 = '10.5.0.7';
    const HOST_CLUSTERCACHE2 = '10.5.0.8';
    const HOST_CLUSTERCACHE3 = '10.5.0.9';
    const HOST_DISCONNECTED_HOST = '10.5.0.10';

    protected string $store = 'clustercache';
}