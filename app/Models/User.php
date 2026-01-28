<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes, InteractsWithMedia;

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * Default attribute values.
     *
     * @var array
     */
    protected $attributes = [
        'notification_settings' => '{"pushEnabled":true,"alertsEnabled":true,"eventsEnabled":true,"reportsEnabled":true}',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'email',
        'password',
        'nome',
        'phone_verified',
        'phone_verified_at',
        'bairro_id',
        'address',
        'avatar_url',
        'notification_settings',
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
            'phone_verified' => 'boolean',
            'phone_verified_at' => 'datetime',
            'address' => 'array',
            'notification_settings' => 'array',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
        ];
    }

    // =====================================================
    // Relationships
    // =====================================================

    /**
     * Get the bairro that the user belongs to.
     */
    public function bairro(): BelongsTo
    {
        return $this->belongsTo(Bairro::class);
    }

    /**
     * Get the reports for the user.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Get the topics for the user.
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    // =====================================================
    // Media Collections (Spatie Media Library)
    // =====================================================

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Register media conversions for avatar.
     * Generates optimized thumbnail and medium sizes.
     */
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->performOnCollections('avatar')
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(300)
            ->height(300)
            ->performOnCollections('avatar')
            ->nonQueued();
    }

    // =====================================================
    // Accessors
    // =====================================================

    /**
     * Get the avatar URL from media library.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('avatar');
        return $media ? $media->getUrl() : null;
    }

    // =====================================================
    // Scopes
    // =====================================================

    /**
     * Scope a query to only include verified users.
     */
    public function scopeVerified($query)
    {
        return $query->where('phone_verified', true);
    }

    /**
     * Scope a query to filter by bairro.
     */
    public function scopeByBairro($query, $bairroId)
    {
        return $query->where('bairro_id', $bairroId);
    }
}
