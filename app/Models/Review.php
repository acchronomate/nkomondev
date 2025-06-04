<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'accommodation_id',
        'rating',
        'comment',
        'host_response',
        'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            // Mettre à jour les statistiques de l'hébergement
            $model->accommodation->updateRatingStatistics();
        });

        static::updated(function ($model) {
            // Si le statut d'approbation change, mettre à jour les stats
            if ($model->isDirty('is_approved') || $model->isDirty('rating')) {
                $model->accommodation->updateRatingStatistics();
            }
        });

        static::deleted(function ($model) {
            // Mettre à jour les statistiques après suppression
            $model->accommodation->updateRatingStatistics();
        });
    }

    /**
     * Get the booking associated with the review.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who wrote the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the accommodation being reviewed.
     */
    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    /**
     * Check if the review can be created.
     */
    public static function canBeCreatedForBooking(Booking $booking): bool
    {
        // Le client peut laisser un avis seulement après le checkout
        return $booking->status === 'completed'
            && $booking->checked_out_at !== null
            && !$booking->review()->exists();
    }

    /**
     * Approve the review.
     */
    public function approve(): void
    {
        $this->update(['is_approved' => true]);
    }

    /**
     * Reject the review.
     */
    public function reject(): void
    {
        $this->update(['is_approved' => false]);
    }

    /**
     * Add host response.
     */
    public function addHostResponse(string $response): void
    {
        $this->update(['host_response' => $response]);
    }

    /**
     * Get rating stars display.
     */
    public function getRatingStarsAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Scope to get only approved reviews.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope to get only pending reviews.
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope to filter by rating.
     */
    public function scopeWithRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope to order by most recent.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
