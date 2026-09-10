<?php

declare(strict_types=1);

namespace Modules\Identity\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Modules\Companies\Models\Company;
use Modules\Companies\Services\CompanyProvisioner;

/**
 * Registration: create the user, their first company, and the Owner membership
 * in a single transaction, then set that company active and dispatch the
 * email-verification notification.
 */
final class RegisterUser
{
    public function __construct(private readonly CompanyProvisioner $provisioner) {}

    /**
     * @param  array{name: string, email: string, password: string, company_name: string}  $input
     */
    public function handle(array $input): User
    {
        $user = DB::transaction(function () use ($input): User {
            $user = new User;
            $user->name = $input['name'];
            $user->email = $input['email'];
            $user->password = $input['password'];
            $user->save();

            $company = $this->provisioner->create($user, $input['company_name']);

            $user->forceFill(['current_company_id' => $company->getKey()])->save();

            return $user;
        });

        event(new Registered($user));

        return $user->refresh()->load('currentCompany');
    }
}
