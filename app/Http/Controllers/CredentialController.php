<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserCredential;
use App\Services\Polymarket\PolymarketClient;
use App\Services\PriceFeed\BinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CredentialController extends Controller
{
    public function edit(): View
    {
        $credential = UserCredential::firstOrCreate(['user_id' => auth()->id()]);

        return view('settings.credentials', compact('credential'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'polymarket_api_key' => ['nullable', 'string', 'max:500'],
            'polymarket_api_secret' => ['nullable', 'string', 'max:500'],
            'polymarket_api_passphrase' => ['nullable', 'string', 'max:500'],
            'polymarket_wallet_address' => ['nullable', 'string', 'max:100', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ]);

        $credential = UserCredential::firstOrCreate(['user_id' => auth()->id()]);

        $data = [];
        $fields = ['polymarket_api_key', 'polymarket_api_secret', 'polymarket_api_passphrase', 'polymarket_wallet_address'];

        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $data[$field] = $request->input($field);
            }
        }

        if (!empty($data)) {
            $credential->update($data);
        }

        return back()->with('success', 'Polymarket credentials updated.');
    }

    public function testPolymarket(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasPolymarketConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'No Polymarket credentials configured. Please save your keys first.',
            ]);
        }

        try {
            $client = new PolymarketClient($user);
            $result = $client->testConnection();

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Connected to Polymarket successfully!' : 'Connection failed. Please check your credentials.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function testBinance(): JsonResponse
    {
        try {
            $binance = app(BinanceService::class);
            $prices = $binance->getMultiAssetPrices();

            $formatted = [];
            foreach ($prices as $asset => $price) {
                $formatted[] = "{$asset}: \${$price}";
            }

            return response()->json([
                'success' => true,
                'message' => 'Binance connected! ' . implode(' | ', $formatted),
                'prices' => $prices,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Binance connection failed: ' . $e->getMessage(),
            ]);
        }
    }
}
