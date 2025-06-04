<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Availability extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'date',
        'available_quantity',
        'price_override',
        'is_blocked',
    ];

    protected $casts = [
        'date' => 'date',
        'available_quantity' => 'integer',
        'price_override' => 'decimal:2',
        'is_blocked' => 'boolean',
        'updated_at' => 'datetime',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->updated_at = $model->freshTimestamp();
        });

        static::updating(function ($model) {
            $model->updated_at = $model->freshTimestamp();
        });
    }

    /**
     * Get the room that owns the availability.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the effective price for this date.
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->price_override ?? $this->room->base_price_per_night;
    }

    /**
     * Check if the room is available on this date.
     */
    public function getIsAvailableAttribute(): bool
    {
        return !$this->is_blocked && $this->available_quantity > 0;
    }

    /**
     * Decrease available quantity.
     */
    public function decreaseQuantity(int $quantity = 1): void
    {
        $this->decrement('available_quantity', $quantity);
    }

    /**
     * Increase available quantity.
     */
    public function increaseQuantity(int $quantity = 1): void
    {
        $newQuantity = $this->available_quantity + $quantity;

        // Ne pas dépasser la quantité totale de la chambre
        if ($newQuantity > $this->room->total_quantity) {
            $newQuantity = $this->room->total_quantity;
        }

        $this->update(['available_quantity' => $newQuantity]);
    }

    /**
     * Scope to get available dates only.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_blocked', false)
            ->where('available_quantity', '>', 0);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
