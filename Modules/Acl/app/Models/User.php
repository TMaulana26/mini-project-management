<?php

namespace Modules\Acl\Models;

use App\Traits\HasActiveStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Notifications\CustomResetPassword;
use Modules\Auth\Notifications\CustomVerifyEmail;
use Modules\Company\app\Models\Company;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasActiveStatus, HasApiTokens, HasFactory, HasRoles, InteractsWithMedia, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $guard_name = 'web';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomVerifyEmail);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPassword($token));
    }

    /**
     * Register media conversions for Spatie Media Library.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(200)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(800)
            ->format('webp')
            ->nonQueued();
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_picture')
            ->singleFile()
            ->useDisk('public');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'company_id',
    ];

    /**
     * Get the company that owns the user.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
