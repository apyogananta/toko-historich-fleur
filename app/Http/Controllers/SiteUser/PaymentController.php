<?php

namespace App\Http\Controllers\SiteUser;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShoppingCartItem;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->initMidtrans();
    }

    private function initMidtrans()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
        Config::$overrideNotifUrl = env('NGROK_HTTP_8000');
    }

    public function initiatePayment(Request $request)
    {
        $cartItems      = $request->input('cartItems');
        $addressId      = $request->input('address_id');
        $shippingOption = $request->input('shipping_option');
        $user           = $request->user();

        if (!$cartItems || count($cartItems) === 0) {
            return response()->json(['error' => 'Keranjang belanja kosong.'], 400);
        }
        if (!$addressId) {
            return response()->json(['error' => 'Alamat pengiriman tidak tersedia.'], 400);
        }
        if (!$shippingOption || !isset($shippingOption['cost']) || !isset($shippingOption['code']) || !isset($shippingOption['service'])) {
            return response()->json(['error' => 'Opsi pengiriman tidak valid atau tidak lengkap.'], 400);
        }

        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $orderItems  = [];
            $itemDetails = [];

            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (!$product) {
                    throw new \Exception('Produk dengan ID ' . $item['product_id'] . ' tidak ditemukan.', 404);
                }

                if ($product->stock < $item['qty']) {
                    throw new \Exception("Stok produk {$product->product_name} (sisa: {$product->stock}) tidak mencukupi untuk jumlah yang diminta ({$item['qty']}).", 400);
                }

                $price    = ($product->sale_price > 0 ? $product->sale_price : $product->original_price);
                $subtotal = $price * $item['qty'];
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'qty'        => $item['qty'],
                    'price'      => $price,
                    'subtotal'   => $subtotal,
                ];

                $itemDetails[] = [
                    'id'       => (string)$product->id,
                    'price'    => (int)$price,
                    'quantity' => (int)$item['qty'],
                    'name'     => substr($product->product_name, 0, 50),
                ];
            }

            $shippingCost = (int)$shippingOption['cost'];
            $totalAmount += $shippingCost;

            if ($shippingCost > 0) {
                $itemDetails[] = [
                    'id'       => 'SHIPPING',
                    'price'    => $shippingCost,
                    'quantity' => 1,
                    'name'     => 'Ongkos Kirim',
                ];
            }

            $orderNumber = 'ORDER-' . time() . '-' . $user->id;

            $order = Order::create([
                'site_user_id'  => $user->id,
                'address_id'    => $addressId,
                'order_number'  => $orderNumber,
                'total_amount'  => $totalAmount,
                'shipping_cost' => $shippingCost,
                'status'        => 'awaiting_payment',
            ]);

            foreach ($orderItems as $orderItemData) {
                $orderItemData['order_id'] = $order->id;
                OrderItem::create($orderItemData);
            }

            Shipment::create([
                'order_id'        => $order->id,
                'courier'         => $shippingOption['code'],
                'service'         => $shippingOption['service'],
                'tracking_number' => null,
                'shipping_cost'   => $shippingCost,
                'status'          => 'pending',
            ]);

            ShoppingCartItem::whereHas('shoppingCart', function ($query) use ($user) {
                $query->where('site_user_id', $user->id);
            })->whereIn('product_id', array_column($cartItems, 'product_id'))
                ->delete();

            DB::commit();
            
            $params = [
                'transaction_details' => [
                    'order_id'     => $orderNumber,
                    'gross_amount' => (int)$totalAmount,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email'      => $user->email,
                    'phone'      => $user->phone_number,
                ],
                'item_details' => $itemDetails,
                'callbacks' => [
                    'finish' => 'http://localhost:5173/payment-success',
                ],
            ];

            $snapToken = Snap::getSnapToken($params);

            return response()->json(['snapToken' => $snapToken, 'order_id' => $orderNumber, 'id' => $order->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Midtrans Snap Exception: ' . $e->getMessage(), ['params' => $params ?? null, 'request' => $request->all()]);
            $midtransError = json_decode($e->getMessage());
            $errorMessage = isset($midtransError->error_messages[0]) ? $midtransError->error_messages[0] : 'Gagal memulai pembayaran dengan Midtrans.';
            return response()->json(['error' => $errorMessage], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Initiate Payment Exception: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Terjadi kesalahan saat memproses pesanan: ' . $e->getMessage()]);
        }
    }

    public function handleNotification(Request $request)
    {
        try {
            $notification = new Notification();
        } catch (\Exception $e) {
            Log::error('Midtrans Notification Exception: Failed to instantiate Notification object.', ['error' => $e->getMessage(), 'payload' => $request->all()]);
            return response()->json(['message' => 'Invalid notification object.'], 400);
        }

        Log::info('Notifikasi Midtrans Diterima:', $request->all());

        $transactionId     = $notification->transaction_id;
        $orderId           = $notification->order_id;
        $transactionStatus = $notification->transaction_status;
        $fraudStatus       = $notification->fraud_status ?? null;
        $paymentType       = $notification->payment_type;
        $grossAmount       = $notification->gross_amount;

        $order = Order::with('orderItems.product')->where('order_number', $orderId)->first(); // Eager load

        if (!$order) {
            Log::warning('Pesanan tidak ditemukan untuk notifikasi Midtrans:', ['order_id' => $orderId]);
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 200);
        }

        if ($order->status !== 'awaiting_payment') {
            Log::info('Notifikasi untuk pesanan yang tidak menunggu pembayaran diterima (dilewati):', ['order_id' => $orderId, 'current_status' => $order->status, 'notification_status' => $transactionStatus]);
            return response()->json(['message' => 'Pesanan tidak dalam status menunggu pembayaran.'], 200);
        }

        DB::beginTransaction();
        try {
            Payment::updateOrCreate(
                ['transaction_id' => $transactionId],
                [
                    'order_id'     => $order->id,
                    'payment_type' => $paymentType,
                    'status'       => $transactionStatus,
                    'amount'       => (int) $grossAmount,
                    'metadata'     => $request->all(),
                ]
            );

            if ($transactionStatus == 'settlement' || ($transactionStatus == 'capture' && $fraudStatus == 'accept')) {
                $order->status = 'pending';

                foreach ($order->orderItems as $item) {
                    if ($item->product) {
                        $productToUpdate = Product::lockForUpdate()->find($item->product_id);
                        if ($productToUpdate) {
                            if ($productToUpdate->stock >= $item->qty) {
                                $productToUpdate->decrement('stock', $item->qty);
                                Log::info("Stok produk #{$item->product_id} dikurangi {$item->qty} untuk order #{$order->order_number}");
                            } else {
                                Log::error("Stok produk #{$item->product_id} tidak cukup ({$productToUpdate->stock}) saat mengurangi {$item->qty} untuk order #{$order->order_number} yg sudah dibayar!");
                            }
                        } else {
                            Log::warning("Produk #{$item->product_id} hilang saat mengurangi stok untuk order #{$order->order_number}");
                        }
                    } else {
                        Log::warning("Relasi produk tidak ditemukan untuk order item ID {$item->id} pada order #{$order->order_number}");
                    }
                }

            } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                $order->status = 'cancelled';
                Log::info("Order #{$order->order_number} dibatalkan karena status pembayaran: {$transactionStatus}. Stok tidak diubah.");
            } else {
                Log::info('Notifikasi status transisi Midtrans diterima:', ['order_id' => $orderId, 'midtrans_status' => $transactionStatus, 'order_status' => $order->status]);
            }

            if ($order->isDirty('status')) {
                $order->save();
                Log::info('Notifikasi Midtrans diproses, status order diperbarui:', ['order_number' => $orderId, 'new_status' => $order->status]);
            } else {
                Log::info('Notifikasi Midtrans diproses, status order tidak berubah:', ['order_number' => $orderId, 'current_status' => $order->status]);
            }


            DB::commit();

            return response()->json(['message' => 'Notifikasi berhasil diproses.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses notifikasi Midtrans di dalam transaksi:', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Gagal memproses notifikasi.'], 500);
        }
    }
}
