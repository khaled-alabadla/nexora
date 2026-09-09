<?php

declare(strict_types=1);

namespace Modules\Companies\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class CompaniesServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Companies';
    }
}
