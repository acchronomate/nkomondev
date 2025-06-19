<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUniqueSlug
{
    /**
     * Generate a unique slug for the model.
     */
    protected function generateUniqueSlug(string $title, string $field = 'slug'): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where($field, $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Boot the trait.
     */
    protected static function bootHasUniqueSlug()
    {
        static::creating(function ($model) {
            if (empty($model->slug) && !empty($model->name)) {
                $name = is_array($model->name) ? ($model->name['fr'] ?? $model->name['en'] ?? '') : $model->name;
                $model->slug = $model->generateUniqueSlug($name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && !$model->isDirty('slug')) {
                $name = is_array($model->name) ? ($model->name['fr'] ?? $model->name['en'] ?? '') : $model->name;
                $model->slug = $model->generateUniqueSlug($name);
            }
        });
    }
}
