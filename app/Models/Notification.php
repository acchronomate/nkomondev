<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'title' => 'array',
        'message' => 'array',
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get title in specific locale.
     */
    public function getTitle(string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->title[$locale] ?? $this->title['fr'] ?? '';
    }

    /**
     * Get message in specific locale.
     */
    public function getMessage(string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->message[$locale] ?? $this->message['fr'] ?? '';
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Create a booking notification.
     */
    public static function createBookingNotification(User $user, Booking $booking, string $type): self
    {
        $titles = [
            'booking_new' => [
                'fr' => 'Nouvelle réservation',
                'en' => 'New booking',
            ],
            'booking_confirmed' => [
                'fr' => 'Réservation confirmée',
                'en' => 'Booking confirmed',
            ],
            'booking_cancelled' => [
                'fr' => 'Réservation annulée',
                'en' => 'Booking cancelled',
            ],
        ];

        $messages = [
            'booking_new' => [
                'fr' => sprintf('Vous avez reçu une nouvelle réservation #%s', $booking->booking_number),
                'en' => sprintf('You have received a new booking #%s', $booking->booking_number),
            ],
            'booking_confirmed' => [
                'fr' => sprintf('Votre réservation #%s a été confirmée', $booking->booking_number),
                'en' => sprintf('Your booking #%s has been confirmed', $booking->booking_number),
            ],
            'booking_cancelled' => [
                'fr' => sprintf('La réservation #%s a été annulée', $booking->booking_number),
                'en' => sprintf('Booking #%s has been cancelled', $booking->booking_number),
            ],
        ];

        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $titles[$type] ?? ['fr' => $type, 'en' => $type],
            'message' => $messages[$type] ?? ['fr' => '', 'en' => ''],
            'data' => [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
            ],
        ]);
    }

    /**
     * Create an invoice notification.
     */
    public static function createInvoiceNotification(User $user, Invoice $invoice): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'invoice_generated',
            'title' => [
                'fr' => 'Nouvelle facture disponible',
                'en' => 'New invoice available',
            ],
            'message' => [
                'fr' => sprintf('Votre facture %s pour %s est disponible', $invoice->invoice_number, $invoice->period),
                'en' => sprintf('Your invoice %s for %s is available', $invoice->invoice_number, $invoice->period),
            ],
            'data' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ],
        ]);
    }

    /**
     * Create a review notification.
     */
    public static function createReviewNotification(User $user, Review $review): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'review_received',
            'title' => [
                'fr' => 'Nouvel avis reçu',
                'en' => 'New review received',
            ],
            'message' => [
                'fr' => sprintf('Un client a laissé un avis %d étoiles pour %s', $review->rating, $review->accommodation->name),
                'en' => sprintf('A guest left a %d star review for %s', $review->rating, $review->accommodation->name),
            ],
            'data' => [
                'review_id' => $review->id,
                'accommodation_id' => $review->accommodation_id,
                'rating' => $review->rating,
            ],
        ]);
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to order by most recent.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
