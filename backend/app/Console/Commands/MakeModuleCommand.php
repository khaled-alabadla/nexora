<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Scaffolds a business module (see ADR-0002) and registers its service
 * provider in bootstrap/providers.php.
 *
 *   php artisan make:module Sales
 */
final class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : The StudlyCase module name (e.g. Sales)}';

    protected $description = 'Scaffold a new business module';

    public function handle(Filesystem $files): int
    {
        $name = Str::studly((string) $this->argument('name'));

        if (! preg_match('/^[A-Z][A-Za-z0-9]+$/', $name)) {
            $this->components->error("Invalid module name [{$name}]. Use StudlyCase letters/digits only.");

            return self::FAILURE;
        }

        $moduleRoot = base_path("modules/{$name}");

        if ($files->isDirectory($moduleRoot)) {
            $this->components->error("Module [{$name}] already exists at modules/{$name}.");

            return self::FAILURE;
        }

        foreach ([
            'Providers',
            'Models',
            'Http/Controllers',
            'Http/Requests',
            'Http/Resources',
            'Services',
            'Routes',
            'Database/Migrations',
            'Database/Factories',
            'Database/Seeders',
            'Tests/Feature',
            'Tests/Unit',
        ] as $dir) {
            $files->ensureDirectoryExists("{$moduleRoot}/{$dir}");
        }

        // Keep otherwise-empty scaffolding directories in version control.
        foreach (['Models', 'Http/Controllers', 'Http/Requests', 'Http/Resources', 'Services', 'Database/Migrations', 'Database/Factories', 'Database/Seeders', 'Tests/Unit'] as $dir) {
            $files->put("{$moduleRoot}/{$dir}/.gitkeep", '');
        }

        $files->put("{$moduleRoot}/Tests/Feature/.gitkeep", '');
        $files->put("{$moduleRoot}/module.json", $this->stubModuleJson($name));
        $files->put("{$moduleRoot}/Providers/{$name}ServiceProvider.php", $this->stubProvider($name));
        $files->put("{$moduleRoot}/Routes/api.php", $this->stubRoutes($name));

        $this->registerProvider($files, $name);

        $this->components->info("Module [{$name}] created at modules/{$name} and registered.");
        $this->components->warn('Run `composer dump-autoload` to pick up the new namespace.');

        return self::SUCCESS;
    }

    private function registerProvider(Filesystem $files, string $name): void
    {
        $path = base_path('bootstrap/providers.php');
        $contents = $files->get($path);
        $fqcn = "Modules\\{$name}\\Providers\\{$name}ServiceProvider::class";

        if (str_contains($contents, $fqcn)) {
            return;
        }

        $contents = preg_replace(
            '/\n\];\s*$/',
            "\n    {$fqcn},\n];\n",
            rtrim($contents)."\n",
        ) ?? $contents;

        $files->put($path, $contents);
    }

    private function stubModuleJson(string $name): string
    {
        return json_encode([
            'name' => $name,
            'description' => "The {$name} module.",
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    }

    private function stubProvider(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Modules\\{$name}\\Providers;

        use App\\Support\\Modules\\ModuleServiceProvider;

        final class {$name}ServiceProvider extends ModuleServiceProvider
        {
            protected function name(): string
            {
                return '{$name}';
            }
        }

        PHP;
    }

    private function stubRoutes(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        use Illuminate\\Support\\Facades\\Route;

        /*
        |--------------------------------------------------------------------------
        | {$name} module API routes
        |--------------------------------------------------------------------------
        |
        | Loaded by {$name}ServiceProvider under the config('nexora.api_prefix')
        | prefix with the "api" middleware group.
        |
        */

        Route::middleware('auth:sanctum')->group(function (): void {
            // Route::apiResource('{$this->routePrefix($name)}', SomeController::class);
        });

        PHP;
    }

    private function routePrefix(string $name): string
    {
        return Str::kebab(Str::plural($name));
    }
}
