<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'slug',
        'postal_code',
        'latitude',
        'longitude',
        'is_popular',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->getName());

                // Ensure unique slug
                $count = 1;
                $originalSlug = $model->slug;
                while (self::where('slug', $model->slug)->exists()) {
                    $model->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    /**
     * Get name in specific locale.
     */
    public function getName(string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->name[$locale] ?? $this->name['fr'] ?? '';
    }

    /**
     * Get the country that owns the city.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the districts for the city.
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /**
     * Get active districts.
     */
    public function activeDistricts(): HasMany
    {
        return $this->districts()->where('is_active', true);
    }

    /**
     * Get the accommodations in this city.
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
     * Get the total number of districts.
     */
    public function getDistrictsCountAttribute(): int
    {
        return $this->districts()->where('is_active', true)->count();
    }

    /**
     * Get full name with country.
     */
    public function getFullNameAttribute(): string
    {
        return $this->getName() . ', ' . $this->country->getName();
    }

    /**
     * Scope to get only active cities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only popular cities.
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Scope to filter by country.
     */
    public function scopeInCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
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
     * Get cities for select options.
     */
    public static function getSelectOptions($countryId = null): array
    {
        $query = self::active()->ordered();

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        return $query->get()
            ->mapWithKeys(function ($city) {
                return [$city->id => $city->getName()];
            })
            ->toArray();
    }

    /**
     * Search cities by name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name->fr', 'like', "%{$search}%")
                ->orWhere('name->en', 'like', "%{$search}%");
        });
    }
}
