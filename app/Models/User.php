<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'linkedin_id',
        'created_by',
        'time_zone_id',
        'user_type',
        'access_token',
        'calendly_access_token',
        'calendly_refresh_token',
        'calendly_token_expires',
        'calendly_organization_uri',
        'daily_profile_email_scraping_count',
        'daily_profile_email_scraping_reset_at',
        'current_organization_id',
        'entitlements',
        'is_platform_admin',
    ];

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
            'entitlements' => 'array',
            'is_platform_admin' => 'boolean',
        ];
    }

    public function currentOrganization()
    {
        return $this->belongsTo(V2Organization::class, 'current_organization_id');
    }
    
    /**
     * Get the user's content preferences
     */
    public function contentPreferences()
    {
        return $this->hasOne(\App\Models\UserContentPreference::class);
    }

    /**
     * Get the user's auto-comment preferences
     */
    public function autoCommentPreferences()
    {
        return $this->hasOne(\App\Models\AutoCommentPreference::class);
    }
}
