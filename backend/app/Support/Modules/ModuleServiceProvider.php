<?php

declare(strict_types=1);

namespace App\Support\Modules;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base provider for every business module (see ADR-0002).
 *
 * A module lives in `modules/<Name>/` and is wired up entirely by its concrete
 * provider extending this class. The provider is registered explicitly in
 * `bootstrap/providers.php` — there is no auto-discovery.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * The module's studly short name, e.g. "Identity", "Sales".
     */
    abstract protected function name(): string;

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerTranslations();
    }

    /**
     * Absolute path inside the module directory.
     */
    protected function modulePath(string $path = ''): string
    {
        return base_path('modules/'.$this->name().($path !== '' ? '/'.ltrim($path, '/') : ''));
    }

    protected function registerRoutes(): void
    {
        $api = $this->modulePath('Routes/api.php');

        if (is_file($api)) {
            Route::middleware('api')
                ->prefix(config('nexora.api_prefix'))
                ->group($api);
        }

        $console = $this->modulePath('Routes/console.php');

        if (is_file($console)) {
            require $console;
        }
    }

    protected function registerMigrations(): void
    {
        $path = $this->modulePath('Database/Migrations');

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    protected function registerTranslations(): void
    {
        $path = $this->modulePath('Lang');

        if (is_dir($path)) {
            $this->loadTranslationsFrom($path, strtolower($this->name()));
        }
    }
}
