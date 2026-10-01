<?php

declare(strict_types=1);

namespace Gauss\Number;

use DivisionByZeroError;
use InvalidArgumentException;

/**
 * Helper internal untuk aritmetika desimal berbasis string (BCMath).
 *
 * - normalize(): validasi + expand notasi ilmiah + hapus trailing zero
 * - add/sub/mul: EXACT (tanpa kehilangan digit)
 * - round/div: pembulatan half-up (away from zero) yang konsisten
 *
 * @internal
 */
final class Decimal
{
    private const PATTERN = '/^([+-]?)(\d*)(?:\.(\d*))?(?:[eE]([+-]?\d+))?$/';
    private const MAX_EXPONENT = 10000;
    /** @internal */
    public const MAX_INTERNAL_SCALE = 100000;

    private function __construct()
    {
    }

    public static function isValid(string $value): bool
    {
        if (!preg_match(self::PATTERN, trim($value), $m)) {
            return false;
        }

        return $m[2] !== '' || ($m[3] ?? '') !== '';
    }

    /**
     * Mengubah string numerik apa pun menjadi bentuk desimal polos
     * yang aman untuk BCMath. "-0" dinormalkan menjadi "0".
     *
     * Catatan presisi:
     * Input `float` yang sudah membawa error floating-point
     * (mis. 0.1 + 0.2 === 0.30000000000000004) TIDAK bisa dikoreksi
     * di sini — error tersebut sudah terjadi sebelum masuk ke library.
     * Gunakan string jika butuh nilai desimal eksak.
     */
    public static function normalize(string $value): string
    {
        $value = trim($value);

        if (
            !preg_match(self::PATTERN, $value, $m)
            || ($m[2] === '' && ($m[3] ?? '') === '')
        ) {
            throw new InvalidArgumentException(
                "Invalid decimal number: {$value}"
            );
        }

        $negative = $m[1] === '-';
        $integer = $m[2];
        $fraction = $m[3] ?? '';
        $exponent = (int) ($m[4] ?? 0);

        if (abs($exponent) > self::MAX_EXPONENT) {
            throw new InvalidArgumentException(
                'Exponent is out of the supported range.'
            );
        }

        if ($exponent !== 0) {
            $digits = $integer . $fraction;
            $point = strlen($integer) + $exponent;

            if ($point <= 0) {
                $integer = '0';
                $fraction = str_repeat('0', -$point) . $digits;
            } elseif ($point >= strlen($digits)) {
                $integer = $digits . str_repeat('0', $point - strlen($digits));
                $fraction = '';
            } else {
                $integer = substr($digits, 0, $point);
                $fraction = substr($digits, $point);
            }
        }

        // Selalu kanonik: hapus trailing zero pada fraksi, baik
        // berasal dari input langsung maupun dari ekspansi eksponen.
        $fraction = rtrim($fraction, '0');

        $integer = ltrim($integer, '0');

        if ($integer === '') {
            $integer = '0';
        }

        $result = $fraction === '' ? $integer : "{$integer}.{$fraction}";

        if ($negative && trim($integer . $fraction, '0') !== '') {
            $result = '-' . $result;
        }

        return $result;
    }

    /**
     * Float => string desimal dengan representasi round-trip terpendek,
     * tidak bergantung pada ini setting `precision` / `serialize_precision`.
     *
     * Peringatan: ini mempertahankan error floating-point yang sudah ada.
     * `fromFloat(0.1 + 0.2)` akan menghasilkan "0.30000000000000004".
     */
    public static function fromFloat(float $value): string
    {
        if (!is_finite($value)) {
            throw new InvalidArgumentException(
                'INF and NAN are not supported.'
            );
        }

        $previous = ini_set('serialize_precision', '-1');

        try {
            $string = var_export($value, true);
        } finally {
            if ($previous !== false) {
                ini_set('serialize_precision', $previous);
            }
        }

        return self::normalize($string);
    }

    /**
     * Pengecekan nol yang exact (tidak terpengaruh scale).
     */
    public static function isZero(string $value): bool
    {
        return self::isZeroCanonical(self::normalize($value));
    }

    /**
     * Pembulatan half-up (away from zero) ke $scale desimal.
     * Hasil selalu memiliki tepat $scale digit desimal.
     */
    public static function round(string $value, int $scale): string
    {
        self::assertScale($scale);

        $value = self::normalize($value);
        $negative = $value[0] === '-';

        if ($negative) {
            $value = substr($value, 1);
        }

        [$integer, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        $fraction = str_pad($fraction, $scale + 1, '0');
        $roundUp = (int) $fraction[$scale] >= 5;

        $result = $scale > 0
            ? $integer . '.' . substr($fraction, 0, $scale)
            : $integer;

        if ($roundUp) {
            $unit = $scale > 0
                ? '0.' . str_repeat('0', $scale - 1) . '1'
                : '1';

            $result = bcadd($result, $unit, $scale);
        }

        if ($negative && !self::isZeroCanonical($result)) {
            $result = '-' . $result;
        }

        return $result;
    }

    public static function add(string $a, string $b): string
    {
        $a = self::normalize($a);
        $b = self::normalize($b);
        $scale = max(self::scaleOfCanonical($a), self::scaleOfCanonical($b));
        self::assertScale($scale);

        return self::normalize(
            bcadd($a, $b, $scale)
        );
    }

    public static function sub(string $a, string $b): string
    {
        $a = self::normalize($a);
        $b = self::normalize($b);
        $scale = max(self::scaleOfCanonical($a), self::scaleOfCanonical($b));
        self::assertScale($scale);

        return self::normalize(
            bcsub($a, $b, $scale)
        );
    }

    public static function mul(string $a, string $b): string
    {
        $a = self::normalize($a);
        $b = self::normalize($b);
        $scaleA = self::scaleOfCanonical($a);
        $scaleB = self::scaleOfCanonical($b);
        self::assertScale($scaleA);
        self::assertScale($scaleB);
        $scale = $scaleA + $scaleB;
        self::assertScale($scale);

        return self::normalize(
            bcmul($a, $b, $scale)
        );
    }

    /**
     * Pembagian dengan hasil dibulatkan half-up ke $scale desimal.
     * bcdiv memotong (toward zero) pada digit ke-(scale+1), sehingga
     * pembulatan half-up dari digit tersebut tepat.
     */
    public static function div(string $a, string $b, int $scale): string
    {
        $a = self::normalize($a);
        $b = self::normalize($b);

        if (self::isZeroCanonical($b)) {
            throw new DivisionByZeroError('Division by zero is undefined.');
        }

        self::assertScale($scale);
        self::assertScale($scale + 1);

        return self::round(bcdiv($a, $b, $scale + 1), $scale);
    }

    public static function compare(string $a, string $b): int
    {
        $a = self::normalize($a);
        $b = self::normalize($b);
        $scale = max(self::scaleOfCanonical($a), self::scaleOfCanonical($b));
        self::assertScale($scale);

        return bccomp($a, $b, $scale);
    }

    /**
     * Jumlah digit desimal pada bentuk ternormalisasi.
     *
     * @internal
     */
    public static function scaleOf(string $value): int
    {
        return self::scaleOfCanonical(self::normalize($value));
    }

    private static function scaleOfCanonical(string $value): int
    {
        $position = strpos($value, '.');

        return $position === false
            ? 0
            : strlen($value) - $position - 1;
    }

    private static function isZeroCanonical(string $value): bool
    {
        return trim(str_replace(['-', '.'], '', $value), '0') === '';
    }

    private static function assertScale(int $scale): void
    {
        if ($scale < 0) {
            throw new InvalidArgumentException(
                'Scale must be non-negative.'
            );
        }

        if ($scale > self::MAX_INTERNAL_SCALE) {
            throw new InvalidArgumentException(
                'Scale exceeds the maximum supported internal scale.'
            );
        }
    }

    /**
     * Orde besaran (floor dari log10(|x|)) dari nilai ternormalisasi.
     *
     * Contoh:
     *   "1"      => 0
     *   "123"    => 2
     *   "0.5"    => -1
     *   "0.001"  => -3
     *   "0"      => 0 (konvensi)
     *
     * Dipakai untuk menentukan scale dinamis pada pembagian agar hasil
     * yang sangat kecil (mis. 1 / 1e100) tidak terpotong menjadi nol.
     *
     * @internal
     */
    public static function orderOf(string $value): int
    {
        $normalized = ltrim(self::normalize($value), '-');
        $dotPos = strpos($normalized, '.');

        if ($dotPos === false) {
            $intPart = $normalized;
            $fracPart = '';
        } else {
            $intPart = substr($normalized, 0, $dotPos);
            $fracPart = substr($normalized, $dotPos + 1);
        }

        $trimmedInt = ltrim($intPart, '0');

        if ($trimmedInt !== '') {
            return strlen($trimmedInt) - 1;
        }

        $firstNonZero = strspn($fracPart, '0');

        if ($firstNonZero === strlen($fracPart)) {
            return 0;
        }

        return -($firstNonZero + 1);
    }
}