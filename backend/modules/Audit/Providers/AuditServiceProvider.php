<?php

declare(strict_types=1);

namespace Modules\Audit\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class AuditServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Audit';
    }
}
