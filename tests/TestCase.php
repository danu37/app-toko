<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.connections.mysql.database') !== 'db_toko_test') {
            throw new RuntimeException('Safety guard: tests must run against db_toko_test, not live db-toko.');
        }

        $this->withoutVite();
    }
}
