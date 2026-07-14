<?php

namespace App\Services\QrTypes;

use App\Models\QrCode;
use Illuminate\Http\Request;

class CryptoHandler implements QrTypeHandler
{
    public function handle(QrCode $qrCode, Request $request): mixed
    {
        $currency = $qrCode->content['currency'] ?? 'BTC';
        $address = $qrCode->content['address'] ?? '';
        $amount = $qrCode->content['amount'] ?? null;
        $label = $qrCode->content['label'] ?? null;

        $uri = $this->buildWalletUri($currency, $address, $amount, $label);

        return view('qr-types.crypto', [
            'qrCode' => $qrCode,
            'currency' => $currency,
            'address' => $address,
            'amount' => $amount,
            'label' => $label,
            'uri' => $uri,
        ]);
    }

    private function buildWalletUri(string $currency, string $address, ?float $amount, ?string $label): string
    {
        $uri = match ($currency) {
            'BTC' => 'bitcoin:',
            'ETH' => 'ethereum:',
            'SOL' => 'solana:',
            'USDT' => '',
            'USDC' => '',
            default => '',
        };

        $uri .= $address;

        $params = [];
        if ($amount !== null && $amount > 0) {
            $params[] = 'amount=' . $amount;
        }
        if ($label !== null && $label !== '') {
            $params[] = 'label=' . urlencode($label);
        }

        if ($params !== []) {
            $uri .= '?' . implode('&', $params);
        }

        return $uri;
    }
}
