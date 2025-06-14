<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Utils\Countries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;


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
        'phone',
        'mobile',
        'store_id',
        'address',
        'address_2',
        'city',
        'state',
        'country',
        'postal_code',
        'is_active',
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
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the country name from country code.
     */
    public function getCountryNameAttribute(): ?string
    {
        return $this->country ? Countries::getName($this->country) : null;
    }

    /**
     * Get the full address formatted.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->address_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country_name,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the formatted ID with leading zeros.
     */
    public function getFormattedIdAttribute(): string
    {
        return str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Validation rules for user creation/update.
     *
     * @param int|null $userId
     * @return array<string, string>
     */
    public static function validationRules(?int $userId = null): array
    {
        $emailRule = 'required|email|unique:users,email';
        if ($userId) {
            $emailRule .= ',' . $userId;
        }

        return [
            'name' => 'required|string',
            'email' => $emailRule,
            'password' => $userId ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
            'phone' => 'nullable|string',
            'mobile' => 'nullable|string',
            'store_id' => 'nullable|exists:stores,id',
            'address' => 'nullable|string',
            'address_2' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string|' . Countries::getValidationRule(),
            'postal_code' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope to filter active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to filter users by store.
     */
    public function scopeByStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Check if user has store assigned.
     */
    public function hasStore(): bool
    {
        return !is_null($this->store_id);
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
