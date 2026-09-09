<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case bindings
|--------------------------------------------------------------------------
|
| Bind TestCase to the core suites and to every module's test directory so
| modules need no per-module Pest bootstrapping (see ADR-0002 / ADR-0005).
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in(__DIR__.'/../modules');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeBalanced', function () {
    // Placeholder for accounting assertions (Phase 5): debits === credits.
    return $this->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function apiUrl(string $path = ''): string
{
    return rtrim(config('nexora.api_prefix').'/'.ltrim($path, '/'), '/');
}
