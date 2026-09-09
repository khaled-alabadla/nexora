<?php

declare(strict_types=1);

namespace Modules\Reports\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class ReportsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Reports';
    }
}
