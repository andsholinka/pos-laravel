<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * `stock` is deliberately NOT fillable: only StockService changes it (via forceFill),
 * so every change is logged in stock_movements.
 */
#[Fillable(['category_id', 'sku', 'barcode', 'name', 'unit', 'cost_price', 'price', 'min_stock', 'image_path', 'is_active'])]
class Product extends Model
{
    use SoftDeletes;

    public const UNITS = ['pcs', 'bungkus', 'botol', 'kaleng', 'kg', 'gram', 'liter', 'pack', 'dus', 'sachet', 'butir', 'cup'];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'cost_price' => 'integer',
            'price' => 'integer',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<SaleItem, $this> */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** @param Builder<Product> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<Product> $query */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('stock', '<=', 'min_stock');
    }

    /** @param Builder<Product> $query */
    public function scopeSearch(Builder $query, ?string $q): void
    {
        $q = trim((string) $q);

        if ($q === '') {
            return;
        }

        $query->where(function (Builder $w) use ($q) {
            $w->where('name', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%")
                ->orWhere('barcode', $q);
        });
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
        );
    }

    protected function isLowStock(): Attribute
    {
        return Attribute::get(fn () => $this->stock <= $this->min_stock);
    }

    /** First letters of the first two words, e.g. "Indomie Goreng" -> "IG". */
    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];

            return Str::upper(collect($words)->take(2)->map(fn ($w) => Str::substr($w, 0, 1))->implode(''));
        });
    }
}
