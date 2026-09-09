<?php

declare(strict_types=1);

namespace Modules\Notifications\Providers;

use App\Support\Modules\ModuleServiceProvider;

final class NotificationsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Notifications';
    }
}
