<?php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class TestCase extends BaseTestCase {
    use CreatesApplication;

    public function setUp(): void
    {
        parent::setUp();
        // additional setup
    }
}