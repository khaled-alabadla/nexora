<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('reports healthy when datastores are reachable', function () {
    getJson(apiUrl('health'))
        ->assertOk()
        ->assertJson([
            'data' => [
                'status' => 'ok',
                'database' => true,
                'cache' => true,
            ],
            'message' => 'OK',
        ]);
});

it('is served under the configured api prefix', function () {
    expect(apiUrl('health'))->toBe('api/v1/health');
});

it('returns JSON for unknown api routes even without an Accept header', function () {
    $this->get(apiUrl('does-not-exist'))
        ->assertNotFound()
        ->assertHeader('content-type', 'application/json');
});
