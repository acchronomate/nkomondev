<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'month',
        'year',
        'total_bookings',
        'total_revenue',
        'commission_rate',
        'commission_amount',
        'currency_id',
        'exchange_rate_used',
        'status',
        'due_date',
        'paid_at',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'total_bookings' => 'integer',
        'total_revenue' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'exchange_rate_used' => 'decimal:6',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->invoice_number)) {
                $model->invoice_number = self::generateInvoiceNumber($model->month, $model->year);
            }

            // Définir la date d'échéance (15 du mois suivant)
            $model->due_date = \Carbon\Carbon::create($model->year, $model->month, 1)
                ->addMonth()
                ->day(15);
        });
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(int $month, int $year): string
    {
        $lastInvoice = self::where('year', $year)
            ->where('month', $month)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastInvoice ? intval(substr($lastInvoice->invoice_number, -4)) + 1 : 1;

        return sprintf('INV-%04d-%02d-%04d', $year, $month, $number);
    }

    /**
     * Get the host (user) who owns the invoice.
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
     * Get the currency used for the invoice.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the invoice period as string.
     */
    public function getPeriodAttribute(): string
    {
        $date = \Carbon\Carbon::create($this->year, $this->month, 1);
        return $date->translatedFormat('F Y');
    }

    /**
     * Get the invoice status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'draft' => 'Brouillon',
            'sent' => 'Envoyée',
            'paid' => 'Payée',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Check if invoice is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'sent' && $this->due_date->isPast();
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['status' => 'sent']);
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Calculate totals from bookings.
     */
    public function calculateTotals(): void
    {
        $bookings = Booking::forHost($this->user_id)
            ->whereMonth('created_at', $this->month)
            ->whereYear('created_at', $this->year)
            ->where('status', 'completed')
            ->get();

        $totalRevenue = 0;
        $totalCommission = 0;

        foreach ($bookings as $booking) {
            // Convertir en devise de l'hébergeur si nécessaire
            if ($booking->currency_id !== $this->currency_id) {
                $amount = $booking->currency->convertTo($this->currency, $booking->total_amount);
                $commission = $booking->currency->convertTo($this->currency, $booking->commission_amount);
            } else {
                $amount = $booking->total_amount;
                $commission = $booking->commission_amount;
            }

            $totalRevenue += $amount;
            $totalCommission += $commission;

            // Créer l'item de facture
            $this->items()->create([
                'booking_id' => $booking->id,
                'description' => sprintf(
                    'Réservation #%s du %s au %s',
                    $booking->booking_number,
                    $booking->check_in->format('d/m/Y'),
                    $booking->check_out->format('d/m/Y')
                ),
                'amount' => $amount,
                'commission_amount' => $commission,
            ]);
        }

        $this->update([
            'total_bookings' => $bookings->count(),
            'total_revenue' => $totalRevenue,
            'commission_amount' => $totalCommission,
        ]);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by period.
     */
    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    /**
     * Scope to get overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'sent')
            ->where('due_date', '<', now());
    }
}
