<?php

declare(strict_types=1);

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Collection;

it('wraps data in the canonical envelope', function () {
    $response = ApiResponse::make(['id' => 1], 'Fetched');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toBe([
            'data' => ['id' => 1],
            'message' => 'Fetched',
        ]);
});

it('defaults to a 200 OK message', function () {
    expect(ApiResponse::make(null)->getData(true))->toBe(['data' => null, 'message' => 'OK']);
});

it('builds a 201 for created resources', function () {
    $response = ApiResponse::created(['id' => 9]);

    expect($response->getStatusCode())->toBe(201)
        ->and($response->getData(true)['message'])->toBe('Created');
});

it('emits a bodyless 204', function () {
    $response = ApiResponse::noContent();

    expect($response->getStatusCode())->toBe(204)
        ->and($response->getContent())->toBe('');
});

it('normalizes Arrayable payloads', function () {
    $response = ApiResponse::make(new Collection(['a' => 1]));

    expect($response->getData(true)['data'])->toBe(['a' => 1]);
});
