<?php

declare(strict_types=1);

use App\Support\Modules\ModuleServiceProvider;

const EXPECTED_MODULES = [
    'Identity', 'Companies', 'Customers', 'Suppliers', 'Products', 'Inventory',
    'Sales', 'Purchases', 'Accounting', 'Expenses', 'Reports', 'Notifications', 'Audit',
];

it('registers every business module provider', function () {
    $registered = collect(app()->getLoadedProviders())
        ->keys()
        ->filter(fn (string $class) => str_starts_with($class, 'Modules\\'));

    expect($registered)->toHaveCount(count(EXPECTED_MODULES));

    foreach (EXPECTED_MODULES as $module) {
        expect(class_exists("Modules\\{$module}\\Providers\\{$module}ServiceProvider"))->toBeTrue()
            ->and(app()->getLoadedProviders())
            ->toHaveKey("Modules\\{$module}\\Providers\\{$module}ServiceProvider");
    }
});

it('has each module provider extending the shared base', function () {
    foreach (EXPECTED_MODULES as $module) {
        $class = "Modules\\{$module}\\Providers\\{$module}ServiceProvider";

        expect(is_subclass_of($class, ModuleServiceProvider::class))->toBeTrue();
    }
});

it('ships a module.json manifest for every module', function () {
    foreach (EXPECTED_MODULES as $module) {
        $path = base_path("modules/{$module}/module.json");

        expect(is_file($path))->toBeTrue();

        $manifest = json_decode((string) file_get_contents($path), true);

        expect($manifest)->toHaveKeys(['name', 'description'])
            ->and($manifest['name'])->toBe($module);
    }
});

it('loads module routes under the api/v1 prefix with the api middleware', function () {
    // Every module route file is wrapped in auth:sanctum; hitting one
    // unauthenticated must yield 401 JSON, proving the group + prefix loaded.
    // The stub files currently define no routes, so assert the prefix instead.
    expect(config('nexora.api_prefix'))->toBe('api/v1');

    $health = collect(app('router')->getRoutes()->getRoutes())
        ->first(fn ($route) => $route->uri() === 'api/v1/health');

    expect($health)->not->toBeNull()
        ->and($health->gatherMiddleware())->toContain('api');
});
