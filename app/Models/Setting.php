<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
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

        // Clear cache when settings are changed
        static::saved(function ($model) {
            Cache::forget('settings.' . $model->key);
            Cache::forget('settings.all');
        });
    }

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember('settings.' . $key, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, $value, string $type = 'string', string $description = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::prepareValue($value, $type),
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Get all settings as array.
     * @param string[] $columns
     */
    public static function all($columns = ['*']): array
    {
        return Cache::remember('settings.all', 3600, function () {
            return self::query()
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => self::castValue($setting->value, $setting->type)];
                })
                ->toArray();
        });
    }

    /**
     * Cast value based on type.
     * @throws \JsonException
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int)$value,
            'decimal' => (float)$value,
            'json' => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            default => $value,
        };
    }

    /**
     * Prepare value for storage.
     */
    protected static function prepareValue($value, string $type): string
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }

    /**
     * Default settings for the application.
     */
    public static function defaults(): array
    {
        return [
            'app_name' => ['value' => 'NKOMON', 'type' => 'string', 'description' => 'Nom de l\'application'],
            'commission_rate' => ['value' => '5', 'type' => 'decimal', 'description' => 'Taux de commission (%)'],
            'default_currency' => ['value' => 'XOF', 'type' => 'string', 'description' => 'Devise par défaut'],
            'supported_locales' => ['value' => ['fr', 'en'], 'type' => 'json', 'description' => 'Langues supportées'],
            'default_locale' => ['value' => 'fr', 'type' => 'string', 'description' => 'Langue par défaut'],
            'notifications_enabled' => ['value' => true, 'type' => 'boolean', 'description' => 'Notifications activées'],
            'auto_confirm_bookings' => ['value' => false, 'type' => 'boolean', 'description' => 'Confirmation automatique des réservations'],
            'invoice_due_days' => ['value' => '15', 'type' => 'integer', 'description' => 'Jours avant échéance facture'],
            'min_booking_hours' => ['value' => '24', 'type' => 'integer', 'description' => 'Heures minimum avant réservation'],
            'max_booking_days' => ['value' => '365', 'type' => 'integer', 'description' => 'Jours maximum pour réservation future'],
        ];
    }

    /**
     * Initialize default settings.
     */
    public static function initializeDefaults(): void
    {
        foreach (self::defaults() as $key => $config) {
            self::set($key, $config['value'], $config['type'], $config['description']);
        }
    }
}
