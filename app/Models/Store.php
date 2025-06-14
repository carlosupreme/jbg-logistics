<?php

namespace App\Models;

use App\Utils\Countries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'office_number',
        'name',
        'supervisor',
        'email',
        'phone',
        'mobile',
        'address',
        'address_2',
        'city',
        'state',
        'country',
        'postal_code',
        'zone_id',
    ];

    /**
     * Get the zone that owns the store.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the country name from country code.
     */
    public function getCountryNameAttribute(): string
    {
        return Countries::getName($this->country) ?? $this->country;
    }

    /**
     * Validation rules for store creation/update.
     *
     * @return array<string, string>
     */
    public static function validationRules(): array
    {
        return [
            'office_number' => 'nullable|string',
            'name' => 'required|string',
            'supervisor' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'mobile' => 'nullable|string',
            'address' => 'nullable|string',
            'address_2' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'required|string' . Countries::getValidationRule(),
            'postal_code' => 'nullable|string',
            'zone_id' => 'required|exists:zones,id',
        ];
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
     * Scope to filter stores by country.
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope to filter stores by zone.
     */
    public function scopeByZone($query, int $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }
}
