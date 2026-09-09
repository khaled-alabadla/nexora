<?php

declare(strict_types=1);

namespace Modules\Purchases\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class PurchasesServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Purchases';
    }
}
