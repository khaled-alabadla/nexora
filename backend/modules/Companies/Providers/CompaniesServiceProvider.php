<?php

declare(strict_types=1);

namespace Modules\Companies\Providers;

use App\Models\User;
use App\Support\Authorization\Permissions;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Tenancy\CompanyContext;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Modules\Companies\Http\Middleware\EnsurePermission;
use Modules\Companies\Http\Middleware\SetActiveCompany;

final class CompaniesServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Companies';
    }

    public function register(): void
    {
        $this->app->singleton(CompanyContext::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerMiddleware();
        $this->registerGates();
    }

    private function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('active-company', SetActiveCompany::class);
        $router->aliasMiddleware('permission', EnsurePermission::class);
    }

    /**
     * Define a gate per permission slug so policies/controllers can use
     * `$user->can('member.invite')` against the current company. Owners pass
     * everything via Gate::before.
     */
    private function registerGates(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            $company = $user->currentCompany;

            if ($company !== null && $user->isOwnerOf($company)) {
                return true;
            }

            return null;
        });

        foreach (Permissions::slugs() as $slug) {
            Gate::define($slug, fn (User $user): bool => $user->hasPermission($slug));
        }
    }
}
