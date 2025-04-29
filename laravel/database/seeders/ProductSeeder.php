<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {       
        Schema::disableForeignKeyConstraints();
        Product::truncate();
        Schema::enableForeignKeyConstraints();
        
        
        $products = [
            [
                'name' => 'Laptop',
                'description' => 'High-performance laptop with 16GB RAM and 512GB SSD',
                'price' => 1299.99
            ],
            [
                'name' => 'Smartphone',
                'description' => 'Latest model with 6.7-inch screen and triple camera',
                'price' => 899.99
            ],
            [
                'name' => 'Wireless Headphones',
                'description' => 'Noise-canceling headphones with 20-hour battery life',
                'price' => 249.99
            ],
            [
                'name' => 'Smart Watch',
                'description' => 'Fitness tracker with heart rate monitor and GPS',
                'price' => 349.99
            ],
            [
                'name' => 'Tablet',
                'description' => '10-inch tablet with retina display and 128GB storage',
                'price' => 499.99
            ]
        ];
        
        foreach ($products as $productData) {
            Product::create($productData);
        }
    }
}
