<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /** Category name => SKU prefix. */
    public const CATEGORY_CODES = [
        'Makanan' => 'MKN',
        'Minuman' => 'MNM',
        'Snack' => 'SNK',
        'Sembako' => 'SMB',
        'Bumbu Dapur' => 'BMB',
        'Perawatan Diri' => 'PRW',
        'Kebutuhan Rumah Tangga' => 'RMT',
        'Frozen Food' => 'FRZ',
    ];

    /** SKU of the product seeded as inactive (demo of the inactive state). */
    public const INACTIVE_SKU = 'SNK-007';

    /**
     * [category, sku, name, unit, cost_price, price, min_stock, popularity (1-10), start_stock]
     * Order defines the ids: id 1 = Indomie Goreng.
     * Popularity/start_stock only drive SalesHistorySeeder; stock is inserted as 0 and set there.
     */
    public const PRODUCTS = [
        ['Makanan', 'MKN-001', 'Indomie Goreng', 'bungkus', 3000, 3500, 20, 10, 120],
        ['Makanan', 'MKN-002', 'Indomie Soto', 'bungkus', 2900, 3500, 15, 7, 80],
        ['Makanan', 'MKN-003', 'Mie Sedaap Goreng', 'bungkus', 2900, 3500, 15, 7, 80],
        ['Makanan', 'MKN-004', 'Pop Mie Ayam', 'cup', 4500, 6000, 10, 5, 48],
        ['Makanan', 'MKN-005', 'Sarden ABC 155g', 'kaleng', 9000, 11500, 6, 3, 24],
        ['Makanan', 'MKN-006', 'Sari Roti Tawar', 'bungkus', 14000, 17000, 5, 3, 20],

        ['Minuman', 'MNM-001', 'Aqua 600ml', 'botol', 2800, 4000, 24, 10, 144],
        ['Minuman', 'MNM-002', 'Teh Botol Sosro 450ml', 'botol', 4200, 5500, 12, 6, 72],
        ['Minuman', 'MNM-003', 'Teh Pucuk Harum 350ml', 'botol', 3000, 4000, 12, 7, 72],
        ['Minuman', 'MNM-004', 'Coca-Cola 390ml', 'botol', 5000, 6500, 12, 5, 48],
        ['Minuman', 'MNM-005', 'Pocari Sweat 500ml', 'botol', 6500, 8000, 8, 4, 36],
        ['Minuman', 'MNM-006', 'Ultra Milk Coklat 250ml', 'pcs', 5200, 6500, 12, 5, 48],
        ['Minuman', 'MNM-007', 'Good Day Cappuccino', 'sachet', 1500, 2000, 20, 6, 100],
        ['Minuman', 'MNM-008', 'Kopi Kapal Api 165g', 'bungkus', 13000, 15500, 6, 3, 24],

        ['Snack', 'SNK-001', 'Chitato 68g', 'bungkus', 9500, 11500, 8, 4, 36],
        ['Snack', 'SNK-002', 'Taro Net 65g', 'bungkus', 7000, 9000, 8, 4, 36],
        ['Snack', 'SNK-003', 'Qtela Singkong', 'bungkus', 6000, 8000, 8, 3, 30],
        ['Snack', 'SNK-004', 'Oreo 133g', 'pcs', 8000, 10000, 8, 4, 36],
        ['Snack', 'SNK-005', 'SilverQueen 58g', 'pcs', 13500, 16500, 6, 3, 24],
        ['Snack', 'SNK-006', 'Beng-Beng', 'pcs', 2000, 2500, 20, 7, 100],
        ['Snack', 'SNK-007', 'Tango Wafer', 'pcs', 8000, 10000, 6, 3, 18],

        ['Sembako', 'SMB-001', 'Beras Pandan Wangi 5kg', 'pack', 68000, 78000, 5, 3, 20],
        ['Sembako', 'SMB-002', 'Minyak Bimoli 2L', 'pack', 36000, 41000, 5, 3, 20],
        ['Sembako', 'SMB-003', 'Gulaku 1kg', 'pack', 15500, 18000, 8, 4, 30],
        ['Sembako', 'SMB-004', 'Telur Ayam', 'kg', 27000, 31000, 5, 5, 25],
        ['Sembako', 'SMB-005', 'Tepung Segitiga Biru 1kg', 'pack', 11500, 14000, 5, 2, 20],
        ['Sembako', 'SMB-006', 'Garam Refina 250g', 'bungkus', 3000, 4000, 6, 2, 24],

        ['Bumbu Dapur', 'BMB-001', 'Kecap Bango 220ml', 'botol', 9500, 12000, 6, 3, 24],
        ['Bumbu Dapur', 'BMB-002', 'Saus Sambal ABC 335ml', 'botol', 11000, 13500, 5, 2, 18],
        ['Bumbu Dapur', 'BMB-003', 'Royco Ayam', 'sachet', 800, 1000, 20, 5, 100],
        ['Bumbu Dapur', 'BMB-004', 'Masako Sapi', 'sachet', 800, 1000, 20, 4, 100],
        ['Bumbu Dapur', 'BMB-005', 'Bawang Putih 250g', 'pack', 9000, 12000, 5, 3, 20],

        ['Perawatan Diri', 'PRW-001', 'Lifebuoy 85g', 'pcs', 3500, 4500, 8, 3, 36],
        ['Perawatan Diri', 'PRW-002', 'Pepsodent 190g', 'pcs', 11000, 13500, 6, 3, 24],
        ['Perawatan Diri', 'PRW-003', 'Pantene 170ml', 'botol', 24000, 28000, 4, 2, 12],
        ['Perawatan Diri', 'PRW-004', 'Clear Sachet', 'sachet', 900, 1000, 20, 4, 100],

        ['Kebutuhan Rumah Tangga', 'RMT-001', 'Rinso Cair 750ml', 'pack', 18000, 22000, 5, 3, 20],
        ['Kebutuhan Rumah Tangga', 'RMT-002', 'Sunlight 755ml', 'pack', 14000, 17500, 5, 3, 20],
        ['Kebutuhan Rumah Tangga', 'RMT-003', 'Baygon Aerosol 600ml', 'kaleng', 32000, 38000, 3, 1, 10],
        ['Kebutuhan Rumah Tangga', 'RMT-004', 'Tisu Paseo 250s', 'pack', 14000, 17000, 5, 2, 18],

        ['Frozen Food', 'FRZ-001', 'So Nice Sosis', 'pcs', 2000, 2500, 20, 6, 100],
        ['Frozen Food', 'FRZ-002', 'Fiesta Nugget 500g', 'pack', 42000, 49000, 4, 2, 15],
        ['Frozen Food', 'FRZ-003', 'Bernardi Bakso 500g', 'pack', 32000, 38000, 4, 2, 12],
        ['Frozen Food', 'FRZ-004', 'Es Krim Paddle Pop', 'pcs', 3500, 5000, 10, 5, 48],
    ];

    public function run(): void
    {
        $categoryIds = Category::query()->pluck('id', 'name');

        foreach (self::PRODUCTS as $i => [$category, $sku, $name, $unit, $cost, $price, $minStock]) {
            Product::query()->create([
                'category_id' => $categoryIds[$category],
                'sku' => $sku,
                'barcode' => self::barcode($i + 1),
                'name' => $name,
                'unit' => $unit,
                'cost_price' => $cost,
                'price' => $price,
                'min_stock' => $minStock,
                'is_active' => $sku !== self::INACTIVE_SKU,
            ]);
        }
    }

    /**
     * Deterministic EAN-13 with the Indonesian GS1 prefix 899 and a valid check digit.
     */
    public static function barcode(int $n): string
    {
        $base = sprintf('899%09d', 210000000 + $n * 104729);

        $sum = 0;
        foreach (str_split($base) as $pos => $digit) {
            // 1-based odd positions weigh 1, even positions weigh 3.
            $sum += (int) $digit * ($pos % 2 === 0 ? 1 : 3);
        }

        return $base.((10 - $sum % 10) % 10);
    }
}
