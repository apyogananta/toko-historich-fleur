<?php

namespace App\Http\Controllers\SiteUser;

use App\Http\Requests\CalculateShippingCostRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\RequestException;

class ShipmentController extends Controller
{
    public function calculateShippingCost(CalculateShippingCostRequest $request)
    {
        $apiKey = env('RAJA_ONGKIR_API_KEY');
        $originPostalCode = env('POSTAL_CODE_ORIGIN');
        $apiUrl = 'https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost';

        if (empty($apiKey) || empty($originPostalCode)) {
            Log::error('Konfigurasi RajaOngkir (API Key / Origin Postal Code) tidak ditemukan / kosong di .env.');
            return response()->json(['message' => 'Konfigurasi server pengiriman tidak lengkap.'], 500);
        }

        try {
            $validatedData = $request->validated();

            $payload = [
                'origin'      => $originPostalCode,
                'destination' => $validatedData['destination'],
                'weight'      => $validatedData['weight'],
                'courier'     => $validatedData['courier'],
            ];

            if (isset($validatedData['price']) && $validatedData['price']) {
                $payload['price'] = $validatedData['price'];
            }

            Log::info('Menghitung ongkos kirim (POST with Query Params):', [
                'url' => $apiUrl,
                'payload_as_query' => $payload
            ]);

            $response = Http::withHeaders([
                'key' => $apiKey,
                'Accept' => 'application/json',
            ])
                ->withOptions([
                    'query' => $payload,
                ])
                ->post($apiUrl);

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['data']) && is_array($responseData['data'])) {
                    return response()->json($responseData['data'], 200);
                } else {
                    Log::warning('Struktur respons sukses API Ongkir tidak memiliki key "data" array:', ['body' => $responseData]);
                    return response()->json([], 200);
                }
            } else {
                Log::error('API Ongkir Error (POST with Query Params):', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body()
                ]);
                $errorMessage = $response->json()['meta']['message'] ??
                    ($response->json()['message'] ??
                        'Gagal menghitung ongkos kirim dari layanan eksternal.');

                if ($response->status() === 400 && str_contains(strtolower($response->body()), 'origin') && str_contains(strtolower($response->body()), 'destination')) {
                    $errorMessage = 'Origin atau Destination (Kode Pos) tidak valid atau tidak ditemukan oleh layanan pengiriman.';
                }

                return response()->json([
                    'message' => $errorMessage,
                ], $response->status());
            }
        } catch (RequestException $e) {
            Log::error('HTTP Request Exception ongkir:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Tidak dapat terhubung ke layanan pengiriman.'], 503);
        } catch (\Exception $e) {
            Log::error('Error umum ongkir:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan internal saat menghitung ongkos kirim.',
            ], 500);
        }
    }
}
