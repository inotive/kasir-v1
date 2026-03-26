<?php

// Algoritma lebih rumit untuk generate kode QR yang tetap bisa di-decode
if (! function_exists('generate_qr_code')) {
    /**
     * Menghasilkan kode QR aman dengan versi, nonce, encoding base62, dan checksum.
     * Format: v1-<nonce62>-<data62>-<checksum6hex>
     */
    function generate_qr_code(int $id): string
    {
        $key = (string) config('qr.secret');
        $version = 'v1';

        // Nonce untuk variasi kode agar tidak mudah ditebak
        $nonce = random_int(1000, 9999);

        // Parameter transformasi berbasis kunci rahasia
        $a = (crc32($key) % 1000) + 700;        // 700..1699
        $b = (crc32(strrev($key)) % 100000) + 50000; // 50000..149999

        // Campuran ID + Nonce, kemudian XOR dengan b agar lebih sulit direka
        $mixed = (($id + $nonce) * $a) ^ $b;

        $encodedMixed = _base62_encode_int($mixed);
        $encodedNonce = _base62_encode_int($nonce);

        // Checksum pendek untuk validasi integritas
        $checksum = substr(hash('sha256', $version.'-'.$encodedNonce.'-'.$encodedMixed.$key), 0, 6);

        return $version.'-'.$encodedNonce.'-'.$encodedMixed.'-'.$checksum;
    }
}

if (! function_exists('decode_qr_code')) {
    /**
     * Mendecode kode QR yang dihasilkan oleh generate_qr_code.
     * Mengembalikan integer ID meja atau null bila tidak valid.
     */
    function decode_qr_code(string $code): ?int
    {
        $key = (string) config('qr.secret');

        $parts = explode('-', trim($code));
        if (count($parts) !== 4) {
            return null; // Format tidak sesuai
        }

        [$version, $nonce62, $data62, $checksum] = $parts;
        if ($version !== 'v1') {
            return null; // Versi tidak dikenal
        }

        // Validasi checksum
        $expected = substr(hash('sha256', $version.'-'.$nonce62.'-'.$data62.$key), 0, 6);
        if (! hash_equals($expected, $checksum)) {
            return null; // Checksum tidak cocok
        }

        $nonce = _base62_decode_str($nonce62);
        $mixed = _base62_decode_str($data62);
        if (! is_int($nonce) || ! is_int($mixed)) {
            return null;
        }

        // Pulihkan parameter transformasi
        $a = (crc32($key) % 1000) + 700;
        $b = (crc32(strrev($key)) % 100000) + 50000;

        // Balikkan XOR dan transformasi
        $value = ($mixed ^ $b);
        if ($value % $a !== 0) {
            return null; // Tidak habis dibagi, indikasi manipulasi
        }

        $id = ($value / $a) - $nonce;

        return $id >= 0 ? (int) $id : null;
    }
}

// Utilitas base62 untuk integer
if (! function_exists('_base62_alphabet')) {
    function _base62_alphabet(): string
    {
        return '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
}

if (! function_exists('_base62_encode_int')) {
    function _base62_encode_int(int $num): string
    {
        $alphabet = _base62_alphabet();
        $base = strlen($alphabet);
        if ($num === 0) {
            return $alphabet[0];
        }
        $res = '';
        while ($num > 0) {
            $res = $alphabet[$num % $base].$res;
            $num = intdiv($num, $base);
        }

        return $res;
    }
}

if (! function_exists('_base62_decode_str')) {
    function _base62_decode_str(string $str): ?int
    {
        $alphabet = _base62_alphabet();
        $base = strlen($alphabet);
        $val = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($alphabet, $str[$i]);
            if ($pos === false) {
                return null;
            }
            $val = $val * $base + $pos;
        }

        return (int) $val;
    }
}
