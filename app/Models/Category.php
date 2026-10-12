<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'color'])]
class Category extends Model
{
    /**
     * Color key => literal Tailwind classes (kept literal so Tailwind does not purge them).
     *
     * @var array<string, array{label: string, bg: string, text: string, ring: string, solid: string}>
     */
    public const COLORS = [
        'emerald' => ['label' => 'Hijau', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-600/20', 'solid' => 'bg-emerald-500'],
        'sky' => ['label' => 'Biru Langit', 'bg' => 'bg-sky-100', 'text' => 'text-sky-700', 'ring' => 'ring-sky-600/20', 'solid' => 'bg-sky-500'],
        'amber' => ['label' => 'Kuning', 'bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'ring' => 'ring-amber-600/20', 'solid' => 'bg-amber-500'],
        'rose' => ['label' => 'Merah Muda', 'bg' => 'bg-rose-100', 'text' => 'text-rose-700', 'ring' => 'ring-rose-600/20', 'solid' => 'bg-rose-500'],
        'violet' => ['label' => 'Ungu', 'bg' => 'bg-violet-100', 'text' => 'text-violet-700', 'ring' => 'ring-violet-600/20', 'solid' => 'bg-violet-500'],
        'orange' => ['label' => 'Oranye', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'ring' => 'ring-orange-600/20', 'solid' => 'bg-orange-500'],
        'teal' => ['label' => 'Toska', 'bg' => 'bg-teal-100', 'text' => 'text-teal-700', 'ring' => 'ring-teal-600/20', 'solid' => 'bg-teal-500'],
        'lime' => ['label' => 'Hijau Muda', 'bg' => 'bg-lime-100', 'text' => 'text-lime-700', 'ring' => 'ring-lime-600/20', 'solid' => 'bg-lime-500'],
        'fuchsia' => ['label' => 'Magenta', 'bg' => 'bg-fuchsia-100', 'text' => 'text-fuchsia-700', 'ring' => 'ring-fuchsia-600/20', 'solid' => 'bg-fuchsia-500'],
        'slate' => ['label' => 'Abu-abu', 'bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'ring' => 'ring-slate-600/20', 'solid' => 'bg-slate-500'],
    ];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            $category->slug = Str::slug($category->name);
        });
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Tailwind classes for this category's color (falls back to emerald).
     *
     * @return array{label: string, bg: string, text: string, ring: string, solid: string}
     */
    public function colorClasses(): array
    {
        return self::COLORS[$this->color] ?? self::COLORS['emerald'];
    }
}
