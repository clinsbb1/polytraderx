<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserCredential;
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
}
