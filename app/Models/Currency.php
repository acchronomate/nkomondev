<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'decimal_places',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'decimal_places' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the exchange rate history for the currency.
     */
    public function exchangeRateHistory(): HasMany
    {
        return $this->hasMany(ExchangeRateHistory::class);
    }

    /**
     * Get the users who prefer this currency.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'preferred_currency_id');
    }

    /**
     * Get the accommodations using this currency.
     */
    public function accommodations(): HasMany
    {
        return $this->hasMany(Accommodation::class);
    }

    /**
     * Get the bookings in this currency.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the invoices in this currency.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Convert an amount from this currency to another.
     */
    public function convertTo(Currency $targetCurrency, float $amount): float
    {
        if ($this->id === $targetCurrency->id) {
            return $amount;
        }

        // Convert to XOF (base currency) first
        $amountInBase = $amount / $this->exchange_rate;

        // Then convert to target currency
        return $amountInBase * $targetCurrency->exchange_rate;
    }

    /**
     * Format an amount in this currency.
     */
    public function format(float $amount): string
    {
        $formatted = number_format($amount, $this->decimal_places, '.', ' ');
        return $this->symbol . ' ' . $formatted;
    }

    /**
     * Scope to get only active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the default currency.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }
}
