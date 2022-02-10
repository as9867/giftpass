<?php

namespace App\Domains\Auth\Models;

use App\Domains\Auth\Models\Traits\Attribute\UserAttribute;
use App\Domains\Auth\Models\Traits\Method\UserMethod;
use App\Domains\Auth\Models\Traits\Relationship\UserRelationship;
use App\Domains\Auth\Models\Traits\Scope\UserScope;
use App\Domains\Auth\Notifications\Frontend\ResetPasswordNotification;
use App\Domains\Auth\Notifications\Frontend\VerifyEmail;
use App\Models\Traits\EncryptableDbAttributes;
use App\Models\Traits\HasOTP;
use App\Models\Traits\HasToken;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWallet;
use DarkGhostHunter\Laraguard\Contracts\TwoFactorAuthenticatable;
use DarkGhostHunter\Laraguard\TwoFactorAuthentication;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User.
 */
class User extends Authenticatable implements MustVerifyEmail, TwoFactorAuthenticatable, Wallet
{
    use HasOTP;
    use HasToken;
    use HasRoles;
    use HasWallet;
    use UserScope;
    use UserMethod;
    use Notifiable;
    use HasFactory;
    use SoftDeletes;
    use Impersonate;
    use HasApiTokens;
    use UserAttribute;
    use UserRelationship;
    use MustVerifyEmailTrait;
    use EncryptableDbAttributes;
    use TwoFactorAuthentication;

    public const TYPE_ADMIN = 'admin';
    public const TYPE_USER = 'user';

    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'email_hash',
        'email_verified_at',
        'mobile_hash',
        'mobile_verified_at',
        'password_changed_at',
        'timezone',
        'last_login_at',
        'last_login_ip',
        'to_be_logged_out',
        'provider',
        'provider_id',
        'is_registered',
        'country_code',
        'authy_id',
        'created_at',
        'updated_at',
        'deleted_at',
        // 'username_hash',
        'permissions',
        'roles'
    ];

    /**
     * @var array
     */
    protected $dates = [
        'last_login_at',
        'email_verified_at',
        'password_changed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'to_be_logged_out' => 'boolean',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'avatar',
    ];

    /**
     * @var string[]
     */
    protected $with = [
        'permissions',
        'roles',
    ];

    protected $encryptable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'mobile',
        'username'
    ];

    protected static function booted()
    {
        static::addGlobalScope('verified', function (Builder $builder) {
            $builder->whereNotNull('email_verified_at')
                ->orWhereNotNull('mobile_verified_at');
        });
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Send the registration verification email.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail());
    }

    /**
     * Return true or false if the user can impersonate an other user.
     *
     * @param void
     * @return  bool
     */
    public function canImpersonate(): bool
    {
        return $this->can('admin.access.user.impersonate');
    }

    /**
     * Return true or false if the user can be impersonate.
     *
     * @param void
     * @return  bool
     */
    public function canBeImpersonated(): bool
    {
        return ! $this->isMasterAdmin();
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
