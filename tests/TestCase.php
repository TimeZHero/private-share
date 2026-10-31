<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Vite;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // `artisan dev` keeps the Vite dev server (and public/hot) running locally;
        // tests always render the built assets.
        Vite::useHotFile(storage_path('framework/testing/no-vite-dev-server'));
    }
}
