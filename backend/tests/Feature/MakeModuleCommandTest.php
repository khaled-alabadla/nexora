<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

const SCRATCH_MODULE = 'ScratchWidgets';

afterEach(function () {
    File::deleteDirectory(base_path('modules/'.SCRATCH_MODULE));

    $providers = base_path('bootstrap/providers.php');
    $contents = File::get($providers);
    $line = '    Modules\\'.SCRATCH_MODULE.'\\Providers\\'.SCRATCH_MODULE."ServiceProvider::class,\n";
    File::put($providers, str_replace($line, '', $contents));
});

it('scaffolds a module directory tree', function () {
    $this->artisan('make:module', ['name' => SCRATCH_MODULE])->assertSuccessful();

    $root = base_path('modules/'.SCRATCH_MODULE);

    expect(File::isDirectory($root))->toBeTrue()
        ->and(File::exists("{$root}/Providers/".SCRATCH_MODULE.'ServiceProvider.php'))->toBeTrue()
        ->and(File::exists("{$root}/Routes/api.php"))->toBeTrue()
        ->and(File::exists("{$root}/module.json"))->toBeTrue()
        ->and(File::isDirectory("{$root}/Http/Controllers"))->toBeTrue()
        ->and(File::isDirectory("{$root}/Database/Migrations"))->toBeTrue();

    $manifest = json_decode(File::get("{$root}/module.json"), true);
    expect($manifest['name'])->toBe(SCRATCH_MODULE);
});

it('registers the module provider in bootstrap/providers.php', function () {
    $this->artisan('make:module', ['name' => SCRATCH_MODULE])->assertSuccessful();

    expect(File::get(base_path('bootstrap/providers.php')))
        ->toContain('Modules\\'.SCRATCH_MODULE.'\\Providers\\'.SCRATCH_MODULE.'ServiceProvider::class');
});

it('rejects an invalid module name', function () {
    $this->artisan('make:module', ['name' => 'bad-name!'])->assertFailed();
});

it('refuses to overwrite an existing module', function () {
    $this->artisan('make:module', ['name' => SCRATCH_MODULE])->assertSuccessful();
    $this->artisan('make:module', ['name' => SCRATCH_MODULE])->assertFailed();
});

it('produces a syntactically valid provider that extends the base', function () {
    $this->artisan('make:module', ['name' => SCRATCH_MODULE])->assertSuccessful();

    $file = base_path('modules/'.SCRATCH_MODULE.'/Providers/'.SCRATCH_MODULE.'ServiceProvider.php');

    expect(File::get($file))
        ->toContain('namespace Modules\\'.SCRATCH_MODULE.'\\Providers;')
        ->toContain('extends ModuleServiceProvider');

    $output = [];
    $status = 0;
    exec('php -l '.escapeshellarg($file), $output, $status);
    expect($status)->toBe(0);
});
