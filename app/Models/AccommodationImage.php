<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationImage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'accommodation_id',
        'path',
        'caption',
        'is_primary',
        'order',
    ];

    protected $casts = [
        'caption' => 'array',
        'is_primary' => 'boolean',
        'order' => 'integer',
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

            // Si c'est la première image, la définir comme primaire
            if (!$model->accommodation->images()->exists()) {
                $model->is_primary = true;
            }

            // Si cette image est définie comme primaire, retirer le statut primaire des autres
            if ($model->is_primary) {
                $model->accommodation->images()
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }

    /**
     * Get the accommodation that owns the image.
     */
    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    /**
     * Get caption in specific locale.
     */
    public function getCaption(string $locale = null): string
    {
        if (!$this->caption) {
            return '';
        }

        $locale = $locale ?? app()->getLocale();
        return $this->caption[$locale] ?? $this->caption['fr'] ?? '';
    }

    /**
     * Get the full URL of the image.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    /**
     * Scope to order by position.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }
}
