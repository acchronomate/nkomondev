<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'type',
        'order',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get name in specific locale.
     */
    public function getName(string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->name[$locale] ?? $this->name['fr'] ?? '';
    }

    /**
     * Get amenities for accommodations.
     */
    public static function forAccommodations()
    {
        return self::whereIn('type', ['accommodation', 'both'])
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Get amenities for rooms.
     */
    public static function forRooms()
    {
        return self::whereIn('type', ['room', 'both'])
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Get amenities as options for select.
     */
    public static function getOptions(string $type = 'both'): array
    {
        $query = self::active()->ordered();

        if ($type === 'accommodation') {
            $query->whereIn('type', ['accommodation', 'both']);
        } elseif ($type === 'room') {
            $query->whereIn('type', ['room', 'both']);
        }

        return $query->get()
            ->mapWithKeys(function ($amenity) {
                return [$amenity->icon => $amenity->getName()];
            })
            ->toArray();
    }

    /**
     * Scope to get only active amenities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by position.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        if ($type === 'both') {
            return $query;
        }

        return $query->whereIn('type', [$type, 'both']);
    }
}
