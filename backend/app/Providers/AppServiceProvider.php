<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureFactories();
    }

    /**
     * Fail loudly in non-production on lazy loading, bad mass-assignment, and
     * accessing missing attributes — cheap insurance for a data-heavy system.
     */
    private function configureModels(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    /**
     * Resolve factories for module models:
     *   Modules\Sales\Models\Invoice
     *     -> Modules\Sales\Database\Factories\InvoiceFactory
     * Falls back to the framework default for App\Models\*.
     */
    private function configureFactories(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            if (Str::startsWith($modelName, 'Modules\\')) {
                $segments = explode('\\', $modelName);
                $module = $segments[1];

                return sprintf(
                    'Modules\\%s\\Database\\Factories\\%sFactory',
                    $module,
                    class_basename($modelName),
                );
            }

            return 'Database\\Factories\\'.class_basename($modelName).'Factory';
        });
    }
}
