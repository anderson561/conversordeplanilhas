<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF for all tests to avoid 419 errors in CI/Testing
        if (class_exists(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)) {
            $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        }
    }
}
