<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_number', 'user_id', 'subtotal', 'discount_type', 'discount_value', 'discount_amount',
    'tax_percent', 'tax_amount', 'total', 'payment_method', 'paid_amount', 'change_amount', 'note',
    'status', 'voided_at', 'voided_by', 'void_reason',
])]
class Sale extends Model
{
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'subtotal' => 'integer',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'discount_amount' => 'integer',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'integer',
            'total' => 'integer',
            'payment_method' => PaymentMethod::class,
            'paid_amount' => 'integer',
            'change_amount' => 'integer',
            'status' => SaleStatus::class,
            'voided_at' => 'datetime',
            'voided_by' => 'integer',
        ];
    }

    /** The cashier who made the sale. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** @param Builder<Sale> $query */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', SaleStatus::Completed->value);
    }

    /**
     * Apply list filters: date_from, date_to (Y-m-d), user_id, payment_method, status, q (invoice).
     *
     * @param  Builder<Sale>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['date_from'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['date_to'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($f['user_id'] ?? null, fn (Builder $q, $v) => $q->where('user_id', $v))
            ->when($f['payment_method'] ?? null, fn (Builder $q, $v) => $q->where('payment_method', $v))
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['q'] ?? null, fn (Builder $q, $v) => $q->where('invoice_number', 'like', '%'.trim($v).'%'));
    }

    public function isVoid(): bool
    {
        return $this->status === SaleStatus::Void;
    }
}
