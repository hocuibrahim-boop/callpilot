<?php

namespace App\Support;

/**
 * Telefon numarasi bicimleme yardimcilari.
 *
 * Onemli: Bu islevler daha once her controller dosyasinda ayri
 * fonksiyon olarak duruyordu; PHP fonksiyonlari dosya bazli
 * yuklendigi icin yalnizca AuthController yuklendiginde tanimliydi.
 * Sinif haline getirilerek her istekte erisilebilir kilindi.
 */
class Phone
{
    /**
     * Numarayi E.164 benzeri tek bicime cevirir.
     *
     *  0532 123 45 67  ->  +905321234567
     *  532 123 45 67   ->  +905321234567
     *  +90 532 ...     ->  +905321234567
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '') {
            return null;
        }

        // 0 ile baslayan 11 haneli yerel numara: 05321234567
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '90' . substr($digits, 1);
        }
        // 0 olmadan 10 haneli: 5321234567
        elseif (strlen($digits) === 10) {
            $digits = '90' . $digits;
        }
        // Ulke kodu olmadan 0'li 10 hane (eski format)
        elseif (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '90' . substr($digits, 1);
        }

        return '+' . $digits;
    }

    /**
     * Ekranda gosterim icin sadelestirir: +905321234567 -> 0532 123 45 67
     */
    public static function pretty(?string $phone): string
    {
        $n = self::normalize($phone);
        if ($n === null) return '';

        $d = ltrim($n, '+');
        if (str_starts_with($d, '90') && strlen($d) === 12) {
            $d = '0' . substr($d, 2);
            return substr($d, 0, 4) . ' ' . substr($d, 4, 3) . ' ' . substr($d, 7, 2) . ' ' . substr($d, 9, 2);
        }

        return $n;
    }
}
