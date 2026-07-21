<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set a default Spatie team ID for testing compatibility
        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
    }
}
