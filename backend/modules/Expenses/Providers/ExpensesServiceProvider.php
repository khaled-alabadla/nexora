<?php

declare(strict_types=1);

namespace Modules\Expenses\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class ExpensesServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Expenses';
    }
}
