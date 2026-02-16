<?php

declare(strict_types=1);

namespace App\Services\Security;

class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 32): string
    {
        $bytes = random_bytes((int) ceil($length * 5 / 8));
        $encoded = $this->base32Encode($bytes);

        return substr($encoded, 0, $length);
    }

    public function verify(string $secret, string $code, int $window = 1, int $digits = 6, int $period = 30): bool
    {
        $normalizedCode = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{6,8}$/', $normalizedCode)) {
            return false;
        }

        $timeSlice = (int) floor(time() / $period);
        for ($i = -$window; $i <= $window; $i++) {
            $generated = $this->hotp($secret, $timeSlice + $i, $digits);
            if (hash_equals($generated, $normalizedCode)) {
                return true;
            }
        }

        return false;
    }

    public function provisioningUri(string $issuer, string $accountName, string $secret): string
    {
        $label = rawurlencode($issuer . ':' . $accountName);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ]);

        return "otpauth://totp/{$label}?{$query}";
    }

    public function qrCodeUrl(string $otpauthUri): string
    {
        return 'https://quickchart.io/qr?size=220&text=' . rawurlencode($otpauthUri);
    }

    private function hotp(string $secret, int $counter, int $digits): string
    {
        $key = $this->base32Decode($secret);
        $binaryCounter = pack('N2', 0, $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);

        $offset = ord(substr($hash, -1)) & 0x0F;
        $segment = substr($hash, $offset, 4);
        $value = unpack('N', $segment)[1] & 0x7FFFFFFF;
        $mod = 10 ** $digits;

        return str_pad((string) ($value % $mod), $digits, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $input): string
    {
        $binary = '';
        $length = strlen($input);

        for ($i = 0; $i < $length; $i++) {
            $binary .= str_pad(decbin(ord($input[$i])), 8, '0', STR_PAD_LEFT);
        }

        $chunks = str_split($binary, 5);
        $output = '';
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $output .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $output;
    }

    private function base32Decode(string $input): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z2-7]/', '', $input) ?? '');
        $binary = '';
        $alphabet = array_flip(str_split(self::BASE32_ALPHABET));

        $length = strlen($clean);
        for ($i = 0; $i < $length; $i++) {
            $char = $clean[$i];
            if (!isset($alphabet[$char])) {
                continue;
            }
            $binary .= str_pad(decbin($alphabet[$char]), 5, '0', STR_PAD_LEFT);
        }

        $bytes = str_split($binary, 8);
        $output = '';
        foreach ($bytes as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }

        return $output;
    }
}

