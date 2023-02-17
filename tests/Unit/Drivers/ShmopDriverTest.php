<?php

namespace Sopamo\ClusterCache\Tests\Unit\Drivers;

use Sopamo\ClusterCache\Drivers\Shmop\ShmopDriver;
use Sopamo\ClusterCache\Tests\TestCase;

class ShmopDriverTest extends TestCase
{
    protected ShmopDriver $shmopDriver;
    public function setUp(): void
    {
        parent::setUp();

        $this->shmopDriver = app(ShmopDriver::class);
    }
    /** @test */
    function memory_key_is_numeric() {
        $this->assertIsNumeric($this->shmopDriver->generateMemoryKey());
    }

    /** @test */
    function it_puts_data() {
        $key = $this->shmopDriver->generateMemoryKey();
        $value = 'value';

        $this->assertTrue($this->shmopDriver->put($key, $value, strlen($value)));
    }

    /** @test */
    function it_deletes_data() {
        $key = $this->shmopDriver->generateMemoryKey();
        $key2 = $this->shmopDriver->generateMemoryKey();
        $value = 'value';

        $this->assertTrue($this->shmopDriver->delete($key, strlen($value)));

        $this->shmopDriver->put($key, $value, strlen($value));

        $this->assertTrue($this->shmopDriver->delete($key2, strlen($value)));
    }

    /** @test */
    function it_gets_data() {
        $key = $this->shmopDriver->generateMemoryKey();
        $value = 'value';

        $this->shmopDriver->put($key, $value, strlen($value));

        $this->assertEquals('value', $this->shmopDriver->get($key, strlen($value)));
    }

    /** @test */
    function it_tries_to_get_data_from_memory_block_which_doesnt_exist() {
        $key = $this->shmopDriver->generateMemoryKey();
        $value = 'value';

        $this->assertNull($this->shmopDriver->get($key, strlen($value)));
    }
}