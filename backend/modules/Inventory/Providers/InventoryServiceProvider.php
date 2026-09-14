<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use App\Support\Modules\ModuleServiceProvider;
use Modules\Inventory\Console\Commands\ReconcileInventoryCommand;

final class InventoryServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Inventory';
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ReconcileInventoryCommand::class,
            ]);
        }
    }
}
