<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'phone_code',
        'currency_code',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get name in specific locale.
     */
    public function getName(string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->name[$locale] ?? $this->name['fr'] ?? $this->code;
    }

    /**
     * Get the currency associated with the country.
     */
    public function currency(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * Get the users associated with the country.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the cities for the country.
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Get active cities.
     */
    public function activeCities(): HasMany
    {
        return $this->cities()->where('is_active', true);
    }

    /**
     * Get the accommodations in this country.
     */
    public function accommodations(): HasMany
    {
        return $this->hasMany(Accommodation::class);
    }

    /**
     * Get the total number of accommodations.
     */
    public function getAccommodationsCountAttribute(): int
    {
        return $this->accommodations()->active()->count();
    }

    /**
     * Get the total number of cities.
     */
    public function getCitiesCountAttribute(): int
    {
        return $this->cities()->where('is_active', true)->count();
    }

    /**
     * Scope to get only active countries.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by name.
     */
    public function scopeOrdered($query)
    {
        $locale = app()->getLocale();
        return $query->orderBy("name->$locale");
    }

    /**
     * Get countries for select options.
     */
    public static function getSelectOptions(): array
    {
        return self::active()
            ->ordered()
            ->get()
            ->mapWithKeys(function ($country) {
                return [$country->id => $country->getName()];
            })
            ->toArray();
    }
}
