<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class SalesServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Sales';
    }
}
