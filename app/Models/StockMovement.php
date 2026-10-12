<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'user_id', 'sale_id', 'type', 'quantity', 'stock_before', 'stock_after', 'reason', 'note'])]
class StockMovement extends Model
{
    public const ADJUSTMENT_REASONS = [
        'stok_opname' => 'Stok Opname',
        'rusak' => 'Barang Rusak',
        'kadaluarsa' => 'Kadaluarsa',
        'hilang' => 'Hilang',
        'retur' => 'Retur ke Supplier',
        'lainnya' => 'Lainnya',
    ];

    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'user_id' => 'integer',
            'sale_id' => 'integer',
            'type' => StockMovementType::class,
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    /** Includes soft-deleted products so history still resolves. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** Human label for the adjustment reason key (falls back to the raw value). */
    public function reasonLabel(): ?string
    {
        return $this->reason ? (self::ADJUSTMENT_REASONS[$this->reason] ?? $this->reason) : null;
    }
}
