<?php

namespace Sopamo\ClusterCache\Tests\Unit;

use Illuminate\Support\Carbon;
use Sopamo\ClusterCache\Drivers\Shmop\ShmopDriver;
use Sopamo\ClusterCache\MetaInformation;
use Sopamo\ClusterCache\Tests\TestCase;

class MetaInformationTest extends TestCase
{
    protected MetaInformation $metaInformation;
    protected array $defaultMetaInformationData = [];
    public function setUp(): void
    {
        parent::setUp();

        $shmopDriver = app(ShmopDriver::class);

        $shmopDriver->delete(MetaInformation::RESERVED_KEY, MetaInformation::RESERVED_LENGTH_IN_BYTES);

        $this->metaInformation = app(MetaInformation::class);

        MetaInformation::setMemoryDriver($shmopDriver);

        $this->defaultMetaInformationData = [
            'memory_key' => $shmopDriver->generateMemoryKey(),
            'is_locked' => false,
            'length' => 5,
            'ttl' => 0,
            'is_being_written' => false,
            'updated_at' => Carbon::now()->timestamp,
        ];
    }

    /** @test */
    function it_puts_data() {
        $key = 'cache key: it_puts_data';

        $this->assertNull($this->metaInformation->get($key));

        $this->assertIsArray($this->metaInformation->put($key, $this->defaultMetaInformationData));
        $this->assertEquals($this->defaultMetaInformationData, $this->metaInformation->put($key, $this->defaultMetaInformationData));
    }

    /** @test */
    function it_updates_data() {
        $key = 'cache key: it_updates_data';

        $this->assertNull($this->metaInformation->get($key));

        $this->metaInformation->put($key, $this->defaultMetaInformationData);

        $this->assertIsArray($this->metaInformation->get($key));
        $this->assertEquals($this->defaultMetaInformationData, $this->metaInformation->get($key));

        $updatedMetaInformationData = [
            ...$this->defaultMetaInformationData,
            'ttl' => 500,
        ];

        $this->metaInformation->put($key, $updatedMetaInformationData);

        $this->assertIsArray($this->metaInformation->get($key));
        $this->assertEquals($updatedMetaInformationData, $this->metaInformation->get($key));
    }

    /** @test */
    function it_gets_data() {
        $key = 'cache key: it_gets_data';

        $this->assertNull($this->metaInformation->get($key));

        $this->metaInformation->put($key, $this->defaultMetaInformationData);

        $this->assertIsArray($this->metaInformation->get($key));
        $this->assertEquals($this->defaultMetaInformationData, $this->metaInformation->get($key));
    }

    /** @test */
    function it_tries_to_get_data_for_cache_key_which_doesnt_exist() {
        $key = 'cache key doesnt exist';

        $this->assertNull($this->metaInformation->get($key));
    }

    /** @test */
    function it_deletes_data() {
        $key = 'cache key: it_deletes_data';

        $this->assertNull($this->metaInformation->get($key));

        $this->metaInformation->put($key, $this->defaultMetaInformationData);

        $this->assertIsArray($this->metaInformation->get($key));
        $this->assertEquals($this->defaultMetaInformationData, $this->metaInformation->get($key));

        $this->metaInformation->delete($key);

        $this->assertNull($this->metaInformation->get($key));
    }
}