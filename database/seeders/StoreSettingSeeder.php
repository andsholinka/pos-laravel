<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::query()->updateOrCreate(['id' => 1], [
            'store_name' => 'Toko Berkah Jaya',
            'address' => 'Jl. Merdeka No. 45, Sumur Bandung, Kota Bandung, Jawa Barat 40111',
            'phone' => '0812-3456-7890',
            'tax_percent' => 0,
            'receipt_footer' => "Terima kasih telah berbelanja!\nBarang yang sudah dibeli tidak dapat ditukar/dikembalikan.",
            'receipt_paper' => '58',
        ]);
    }
}
