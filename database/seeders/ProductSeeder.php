<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allPermissions = [
            'FE',
            'OTO1',
            'OTO2',
            'OTO3',
            'OTO4',
            'OTO5',
            'OTO6',
            'OTO7',
            'OTO8',
            'Bundle',
        ];

        $products = [
            [
                'product_id' => '433885',
                'name' => 'FE Access',
                'funnel' => ['FE'],
            ],
            [
                'product_id' => '434227',
                'name' => 'Full Access',
                'funnel' => $allPermissions,
            ],
            [
                'product_id' => '434229',
                'name' => 'Full Access',
                'funnel' => $allPermissions,
            ],
            [
                'product_id' => '433887',
                'name' => 'Full Access',
                'funnel' => $allPermissions,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['product_id' => $product['product_id']],
                $product
            );
        }
    }
}
