<?php

declare(strict_types=1);

namespace Modules\Suppliers\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class SuppliersServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Suppliers';
    }
}
