<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'name',
        'slug',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
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
                $model->slug = Str::slug($model->getName() . '-' . $model->city->getName());

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
     * Get the city that owns the district.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the country through city.
     */
    public function country()
    {
        return $this->city->country();
    }

    /**
     * Get the accommodations in this district.
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
     * Get full name with city.
     */
    public function getFullNameAttribute(): string
    {
        return $this->getName() . ', ' . $this->city->getName();
    }

    /**
     * Get complete address.
     */
    public function getCompleteAddressAttribute(): string
    {
        return $this->getName() . ', ' . $this->city->getName() . ', ' . $this->city->country->getName();
    }

    /**
     * Scope to get only active districts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by city.
     */
    public function scopeInCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
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
     * Get districts for select options.
     */
    public static function getSelectOptions($cityId = null): array
    {
        $query = self::active()->ordered();

        if ($cityId) {
            $query->where('city_id', $cityId);
        }

        return $query->get()
            ->mapWithKeys(function ($district) {
                return [$district->id => $district->getName()];
            })
            ->toArray();
    }

    /**
     * Search districts by name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name->fr', 'like', "%{$search}%")
                ->orWhere('name->en', 'like', "%{$search}%");
        });
    }
}
