<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            return DB::transaction(function () use ($request) {
                $totalPrice = 0;
                $order = Order::create(['user_id' => $request->user()->id, 'total_price' => 0]);

                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);

                    if ($product->stock < $item['quantity']) {
                        throw new Exception("Insufficient stock for product {$product->name}");
                    }

                    $product->decrement('stock', $item['quantity']);
                    $priceAtPurchase = $product->price * $item['quantity'];
                    $totalPrice += $priceAtPurchase;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price_at_purchase' => $priceAtPurchase,
                    ]);
                }

                $order->update(['total_price' => $totalPrice]);
                return response()->json(['message' => 'Order placed successfully', 'data' => $order], 201);
            });
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $order = Order::with('orderitems')->findOrFail($id);
            return response()->json($order);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function index(Request $request)
    {
        try {
            $orders = Order::with('orderitems')
                           ->where('user_id', $request->user()->id)
                           ->get();
            return response()->json($orders);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}