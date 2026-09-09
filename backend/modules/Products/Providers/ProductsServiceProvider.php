<?php

declare(strict_types=1);

namespace Modules\Products\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class ProductsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Products';
    }
}
