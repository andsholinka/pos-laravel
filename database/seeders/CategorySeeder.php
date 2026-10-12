<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Ids follow this order: 1 Makanan ... 8 Frozen Food.
     */
    public function run(): void
    {
        $categories = [
            ['Makanan', 'orange', 'Mi instan, makanan kaleng, dan roti.'],
            ['Minuman', 'sky', 'Air mineral, teh, kopi, susu, dan minuman ringan.'],
            ['Snack', 'amber', 'Keripik, biskuit, cokelat, dan camilan lainnya.'],
            ['Sembako', 'emerald', 'Beras, minyak, gula, telur, dan kebutuhan pokok.'],
            ['Bumbu Dapur', 'rose', 'Kecap, saus, kaldu, dan bumbu masak.'],
            ['Perawatan Diri', 'violet', 'Sabun, sampo, pasta gigi, dan perlengkapan mandi.'],
            ['Kebutuhan Rumah Tangga', 'teal', 'Deterjen, sabun cuci piring, tisu, dan obat nyamuk.'],
            ['Frozen Food', 'fuchsia', 'Sosis, nugget, bakso, dan es krim.'],
        ];

        foreach ($categories as [$name, $color, $description]) {
            Category::query()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $description,
                'color' => $color,
            ]);
        }
    }
}
