<?php

namespace Tests\Feature;

use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function test_uses_sqlite_in_memory_database(): void
    {
        $this->assertSame(
            'sqlite',
            config('database.default')
        );

        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database')
        );
    }
}