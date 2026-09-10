<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Companies\Concerns\HasCompanyMemberships;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\CarbonInterface|null $email_verified_at
 * @property int|null $current_company_id
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasCompanyMemberships;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * Mass-assignable attributes. Never includes current_company_id,
     * email_verified_at, or any role/company identifier — those are set
     * explicitly by services.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
