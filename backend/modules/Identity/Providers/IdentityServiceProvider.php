<?php

declare(strict_types=1);

namespace Modules\Identity\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class IdentityServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Identity';
    }
}
