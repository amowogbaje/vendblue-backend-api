<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => "Joseph NC",
            "email" => 'josephnc@gmail.com',
            "password" => "password",
            "is_admin" =>true
        ]);
        User::factory(10)->create();

        Product::factory(10)->create();

        Order::factory(15)
            ->create()
            ->each(function ($order) {
                $products = Product::inRandomOrder()->take(rand(1, 5))->get();
                $totalPrice = 0;

                foreach ($products as $product) {
                    $quantity = rand(1, 3);
                    $totalPrice += $product->price * $quantity;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price_at_purchase' => $product->price,
                    ]);
                }
                $order->update(['total_price' => $totalPrice]);
            });
    }
}
