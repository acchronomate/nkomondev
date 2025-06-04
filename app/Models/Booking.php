<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'user_id',
        'room_id',
        'check_in',
        'check_out',
        'guests_adults',
        'guests_children',
        'room_price',
        'total_nights',
        'subtotal',
        'commission_rate',
        'commission_amount',
        'total_amount',
        'currency_id',
        'exchange_rate_used',
        'guest_name',
        'guest_email',
        'guest_phone',
        'special_requests',
        'status',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'guests_adults' => 'integer',
        'guests_children' => 'integer',
        'room_price' => 'decimal:2',
        'total_nights' => 'integer',
        'subtotal' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exchange_rate_used' => 'decimal:6',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->booking_number)) {
                $model->booking_number = self::generateBookingNumber();
            }

            // Calculer le nombre de nuits
            $model->total_nights = $model->check_in->diffInDays($model->check_out);

            // Calculer les montants
            $model->subtotal = $model->room_price * $model->total_nights;
            $model->commission_amount = $model->subtotal * ($model->commission_rate / 100);
            $model->total_amount = $model->subtotal;
        });
    }

    /**
     * Generate a unique booking number.
     */
    public static function generateBookingNumber(): string
    {
        $year = date('Y');
        $lastBooking = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastBooking ? intval(substr($lastBooking->booking_number, -6)) + 1 : 1;

        return sprintf('BK-%s-%06d', $year, $number);
    }

    /**
     * Get the user who made the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the room that was booked.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the accommodation through room.
     */
    public function accommodation()
    {
        return $this->room->accommodation();
    }

    /**
     * Get the currency used for the booking.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the status history for the booking.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    /**
     * Get the review for the booking.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Get the invoice item for this booking.
     */
    public function invoiceItem(): HasOne
    {
        return $this->hasOne(InvoiceItem::class);
    }

    /**
     * Get total guests.
     */
    public function getTotalGuestsAttribute(): int
    {
        return $this->guests_adults + $this->guests_children;
    }

    /**
     * Check if booking can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'])
            && $this->check_in->isFuture();
    }

    /**
     * Confirm the booking.
     */
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->logStatusChange('confirmed');
    }

    /**
     * Cancel the booking.
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        $this->logStatusChange('cancelled', $reason);

        // Restore room availability
        $this->restoreAvailability();
    }

    /**
     * Mark as checked in.
     */
    public function checkIn(): void
    {
        $this->update([
            'checked_in_at' => now(),
        ]);
    }

    /**
     * Mark as checked out.
     */
    public function checkOut(): void
    {
        $this->update([
            'status' => 'completed',
            'checked_out_at' => now(),
        ]);

        $this->logStatusChange('completed');
    }

    /**
     * Log status change.
     */
    protected function logStatusChange(string $status, string $notes = null): void
    {
        $this->statusHistory()->create([
            'status' => $status,
            'changed_by' => auth()->id(),
            'notes' => $notes,
        ]);
    }

    /**
     * Restore room availability after cancellation.
     */
    protected function restoreAvailability(): void
    {
        $startDate = $this->check_in->copy();
        $endDate = $this->check_out->copy();

        while ($startDate->lt($endDate)) {
            $availability = Availability::firstOrCreate(
                [
                    'room_id' => $this->room_id,
                    'date' => $startDate->format('Y-m-d'),
                ],
                [
                    'available_quantity' => $this->room->total_quantity,
                ]
            );

            $availability->increaseQuantity(1);
            $startDate->addDay();
        }
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get bookings for a specific host.
     */
    public function scopeForHost($query, int $hostId)
    {
        return $query->whereHas('room.accommodation', function ($q) use ($hostId) {
            $q->where('user_id', $hostId);
        });
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('check_in', [$startDate, $endDate])
                ->orWhereBetween('check_out', [$startDate, $endDate]);
        });
    }
}
