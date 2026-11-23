<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Requests come from the SPA, like the browser sends them. Sanctum only starts a
        // session (and so cookie auth) for requests from a stateful domain.
        $this->withHeader('Referer', (string) config('app.frontend_url'));
    }
}
