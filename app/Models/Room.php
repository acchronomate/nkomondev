<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'accommodation_id',
        'name',
        'description',
        'room_type',
        'capacity_adults',
        'capacity_children',
        'base_price_per_night',
        'size_sqm',
        'bed_type',
        'amenities',
        'total_quantity',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'amenities' => 'array',
        'capacity_adults' => 'integer',
        'capacity_children' => 'integer',
        'base_price_per_night' => 'decimal:2',
        'size_sqm' => 'integer',
        'total_quantity' => 'integer',
    ];

    /**
     * Get the accommodation that owns the room.
     */
    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    /**
     * Get the images for the room.
     */
    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class);
    }

    /**
     * Get the availabilities for the room.
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    /**
     * Get the bookings for the room.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
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
     * Get description in specific locale.
     */
    public function getDescription(string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->description[$locale] ?? $this->description['fr'] ?? '';
    }

    /**
     * Get total capacity.
     */
    public function getTotalCapacityAttribute(): int
    {
        return $this->capacity_adults + $this->capacity_children;
    }

    /**
     * Get availability for a specific date.
     */
    public function getAvailabilityForDate(string $date): ?Availability
    {
        return $this->availabilities()->where('date', $date)->first();
    }

    /**
     * Get available quantity for a specific date.
     */
    public function getAvailableQuantityForDate(string $date): int
    {
        $availability = $this->getAvailabilityForDate($date);

        if ($availability && $availability->is_blocked) {
            return 0;
        }

        return $availability ? $availability->available_quantity : $this->total_quantity;
    }

    /**
     * Get price for a specific date.
     */
    public function getPriceForDate(string $date): float
    {
        $availability = $this->getAvailabilityForDate($date);

        return $availability && $availability->price_override
            ? $availability->price_override
            : $this->base_price_per_night;
    }

    /**
     * Check if room is available for date range.
     */
    public function isAvailableForDateRange(string $checkIn, string $checkOut, int $quantity = 1): bool
    {
        $startDate = \Carbon\Carbon::parse($checkIn);
        $endDate = \Carbon\Carbon::parse($checkOut);

        // Check each day in the range (excluding checkout day)
        while ($startDate->lt($endDate)) {
            if ($this->getAvailableQuantityForDate($startDate->format('Y-m-d')) < $quantity) {
                return false;
            }
            $startDate->addDay();
        }

        return true;
    }

    /**
     * Calculate total price for date range.
     */
    public function calculateTotalPrice(string $checkIn, string $checkOut): float
    {
        $total = 0;
        $startDate = \Carbon\Carbon::parse($checkIn);
        $endDate = \Carbon\Carbon::parse($checkOut);

        while ($startDate->lt($endDate)) {
            $total += $this->getPriceForDate($startDate->format('Y-m-d'));
            $startDate->addDay();
        }

        return $total;
    }

    /**
     * Scope to filter by capacity.
     */
    public function scopeWithMinCapacity($query, int $adults, int $children = 0)
    {
        return $query->where('capacity_adults', '>=', $adults)
            ->where('capacity_children', '>=', $children);
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
