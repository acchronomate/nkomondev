<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Accommodation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'slug',
        'description',
        'address',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'phone',
        'email',
        'website',
        'amenities',
        'check_in_time',
        'check_out_time',
        'min_stay_days',
        'max_stay_days',
        'currency_id',
        'rating_average',
        'total_reviews',
        'status',
    ];

    protected $casts = [
        'description' => 'array',
        'amenities' => 'array',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'min_stay_days' => 'integer',
        'max_stay_days' => 'integer',
        'rating_average' => 'decimal:1',
        'total_reviews' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name')) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Get the owner (host) of the accommodation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user relationship.
     */
    public function host(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the currency used by this accommodation.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the rooms for the accommodation.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get the images for the accommodation.
     */
    public function images(): HasMany
    {
        return $this->hasMany(AccommodationImage::class);
    }

    /**
     * Get the primary image.
     */
    public function primaryImage()
    {
        return $this->images()->where('is_primary', true)->first();
    }

    /**
     * Get the reviews for the accommodation.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get approved reviews only.
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    /**
     * Get the bookings through rooms.
     */
    public function bookings()
    {
        return $this->hasManyThrough(Booking::class, Room::class);
    }

    /**
     * Get the minimum price among all rooms.
     */
    public function getMinPriceAttribute(): ?float
    {
        return $this->rooms()->min('base_price_per_night');
    }

    /**
     * Get the maximum price among all rooms.
     */
    public function getMaxPriceAttribute(): ?float
    {
        return $this->rooms()->max('base_price_per_night');
    }

    /**
     * Get description in specific locale.
     */
    public function getDescription(string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->description[$locale] ?? $this->description['fr'] ?? '';
    }

    /**
     * Update rating statistics.
     */
    public function updateRatingStatistics(): void
    {
        $stats = $this->approvedReviews()
            ->selectRaw('AVG(rating) as average, COUNT(*) as count')
            ->first();

        $this->update([
            'rating_average' => $stats->average ?? 0,
            'total_reviews' => $stats->count ?? 0,
        ]);
    }

    /**
     * Scope to get only active accommodations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by city.
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by amenities.
     */
    public function scopeWithAmenities($query, array $amenities)
    {
        foreach ($amenities as $amenity) {
            $query->whereJsonContains('amenities', $amenity);
        }
        return $query;
    }
}
