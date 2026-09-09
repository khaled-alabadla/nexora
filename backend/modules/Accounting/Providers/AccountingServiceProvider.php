<?php

declare(strict_types=1);

namespace Modules\Accounting\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class AccountingServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Accounting';
    }
}
