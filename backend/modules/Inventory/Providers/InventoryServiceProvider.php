<?php

declare(strict_types=1);

namespace Modules\Inventory\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class InventoryServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Inventory';
    }
}
