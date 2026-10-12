<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable(['store_name', 'address', 'phone', 'logo_path', 'tax_percent', 'receipt_footer', 'receipt_paper'])]
class StoreSetting extends Model
{
    public const CACHE_KEY = 'store_settings';

    protected function casts(): array
    {
        return [
            'tax_percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * The single store settings row (created on first access), cached forever.
     *
     * Only the raw attribute array is cached: Laravel's cache refuses to unserialize objects
     * (config cache.serializable_classes = false), so the model is rehydrated from it.
     */
    public static function current(): self
    {
        $attributes = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->firstOrCreate([], ['store_name' => config('app.name')])->getAttributes(),
        );

        return (new static)->newFromBuilder($attributes);
    }

    /** Public URL of the store logo, or null when none is uploaded. */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
        );
    }
}
