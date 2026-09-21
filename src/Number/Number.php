<?php

declare(strict_types=1);

namespace Gauss\Number;

use DivisionByZeroError;
use InvalidArgumentException;
use LogicException;

final class Number
{
    private const SCALE = 50;
    private const INTERNAL_SCALE = 60;
    private const EXP_WORK_SCALE = 80;
    private const EXP_GUARD = 30;
    private const MAX_EXPONENT = 10000;
    private const MAX_EXP_ARGUMENT = 10000;

    /**
     * Batas atas log10(e). Nilai sebenarnya ~0.4342944819; kita
     * memakai 0.4343 sebagai batas aman (strictly greater), sehingga
     * perhitungan integer digits exp() selalu over-estimate.
     */
    private const LOG10_E_UPPER = '0.4343';

    private function __construct(
        private readonly string $value,
    ) {
    }

    public static function pi(): self
    {
        return self::of('3.14159265358979323846264338327950288419716939937511');
    }

    public static function e(): self
    {
        return self::of('2.71828182845904523536028747135266249775724709369996');
    }

    public static function of(int|float|string|self $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_int($value)) {
            return new self((string) $value);
        }

        if (is_float($value)) {
            // Catatan: error floating-point yang sudah ada di $value
            // tidak bisa dikoreksi. Gunakan string untuk nilai eksak.
            return new self(Decimal::fromFloat($value));
        }

        $value = trim($value);

        if (!Decimal::isValid($value)) {
            throw new InvalidArgumentException(
                "Unsupported real numeric value: {$value}"
            );
        }

        return new self(Decimal::normalize($value));
    }

    public function one(): self
    {
        return self::of(1);
    }

    public function add(int|float|string|self $other): self
    {
        $other = self::of($other);

        return new self(
            Decimal::add($this->value, $other->value)
        );
    }

    public function sub(int|float|string|self $other): self
    {
        $other = self::of($other);

        return new self(
            Decimal::sub($this->value, $other->value)
        );
    }

    public function mul(int|float|string|self $other): self
    {
        $other = self::of($other);

        return new self(
            Decimal::mul($this->value, $other->value)
        );
    }

    public function div(int|float|string|self $other): self
    {
        $other = self::of($other);

        // Zero-check exact (bukan bccomp dengan scale tetap yang bisa
        // salah menganggap 1e-100 sebagai nol).
        if (Decimal::isZero($other->value)) {
            throw new DivisionByZeroError(
                'Division by zero is undefined.'
            );
        }

        // Magnitude-aware scale:
        //   Jika |divisor| >> |dividend|, hasil sangat kecil dan butuh
        //   digit desimal ekstra. Contoh: 1 / 1e100 = 1e-100 butuh
        //   ~100 digit sebelum significant digit pertama.
        $magnitudeAdjust = max(
            0,
            Decimal::orderOf($other->value)
                - Decimal::orderOf($this->value)
        );

        $scale = self::INTERNAL_SCALE
            + $magnitudeAdjust
            + max(
                Decimal::scaleOf($this->value),
                Decimal::scaleOf($other->value)
            );

        // Normalize untuk menghilangkan trailing zero (mis. 1/2 → "0.5"
        // bukan "0.5000...0").
        return new self(
            Decimal::normalize(
                Decimal::div($this->value, $other->value, $scale)
            )
        );
    }

    public function mod(int|float|string|self $other): self
    {
        $other = self::of($other);

        $a = $this->integerString();
        $b = $other->integerString();

        if ($b === '0') {
            throw new DivisionByZeroError(
                'Division by zero is undefined.'
            );
        }

        $remainder = bcmod($a, $b, 0);

        if (str_starts_with($remainder, '-')) {
            $remainder = bcadd(
                $remainder,
                ltrim($b, '-'),
                0
            );
        }

        return new self(Decimal::normalize($remainder));
    }

    public function compare(int|float|string|self $other): int
    {
        $other = self::of($other);

        // Exact: tidak dibulatkan ke SCALE, sehingga selisih seperti
        // 1e-60 vs 0 tetap terdeteksi.
        return Decimal::compare($this->value, $other->value);
    }

    public function abs(): self
    {
        if (str_starts_with($this->value, '-')) {
            return new self(substr($this->value, 1));
        }

        return $this;
    }

    public function round(int $scale): self
    {
        return new self(Decimal::normalize(Decimal::round($this->value, $scale)));
    }

    public function pow(int $exponent): self
    {
        if (
            $exponent === PHP_INT_MIN
            || abs($exponent) > self::MAX_EXPONENT
        ) {
            throw new InvalidArgumentException(
                'Exponent must be within +/-' . self::MAX_EXPONENT . '.'
            );
        }

        if ($exponent < 0) {
            return $this->one()->div(
                $this->pow(-$exponent)
            );
        }

        $result = $this->one();
        $factor = $this;
        $remaining = $exponent;

        while ($remaining > 0) {
            if (($remaining & 1) === 1) {
                $result = $result->mul($factor);
            }

            $remaining >>= 1;

            if ($remaining > 0) {
                $factor = $factor->mul($factor);
            }
        }

        return $result;
    }

    public function sqrt(): self
    {
        $x = Decimal::normalize($this->value);

        if (str_starts_with($x, '-')) {
            throw new LogicException(
                'Square root requires a non-negative real number.'
            );
        }

        if (Decimal::isZero($x)) {
            return $this;
        }

        // bcsqrt memotong pada digit ke-(SCALE+1); Decimal::round
        // membulatkan half-up, lalu normalize agar konsisten dengan
        // hasil aritmetika lain (mis. sqrt(4) => "2", bukan "2.000...").
        return new self(
            Decimal::normalize(
                Decimal::round(
                    bcsqrt($x, self::SCALE + 1),
                    self::SCALE
                )
            )
        );
    }

    public function exp(): self
    {
        $x = Decimal::normalize($this->value);

        if (Decimal::isZero($x)) {
            return self::of(1);
        }

        $negative = str_starts_with($x, '-');
        $absolute = $negative ? substr($x, 1) : $x;

        // Exact boundary check: Decimal::compare menormalisasi kedua
        // operand dan membandingkan secara exact, tanpa truncation
        // pada scale tetap 60.
        if (
            Decimal::compare(
                $absolute,
                (string) self::MAX_EXP_ARGUMENT
            ) > 0
        ) {
            throw new InvalidArgumentException(
                'exp() argument must be within +/-'
                . self::MAX_EXP_ARGUMENT
                . '.'
            );
        }

        // Hitung perkiraan jumlah digit integer pada exp(|x|) TANPA
        // floating point.
        //
        //   integerDigits(exp(y)) = floor(y * log10(e)) + 1
        //
        // Karena log10(e) < 0.4343, gunakan batas aman:
        //
        //   integerDigits <= floor(|x| * 0.4343) + 1
        //
        // bcmul(..., 0) memotong fraksi ke integer, lalu +1.
        $integerDigits = (int) bcadd(
            bcmul($absolute, self::LOG10_E_UPPER, 0),
            '1',
            0
        );

        // Work scale:
        //   SCALE           → digit final yang diinginkan
        //   integerDigits   → cadangan untuk kasus negatif (inversi
        //                     1/exp(x) menggeser digit ke kanan)
        //   EXP_GUARD       → buffer akumulasi truncation Taylor + squaring
        $work = max(
            self::EXP_WORK_SCALE,
            self::SCALE + $integerDigits + self::EXP_GUARD
        );

        // Argument reduction: exp(x) = exp(x / 2^k) ^ (2^k),
        // dengan |x/2^k| <= 1.
        $reduced = $absolute;
        $squares = 0;

        while (bccomp($reduced, '1', $work) > 0) {
            $reduced = bcdiv($reduced, '2', $work);
            $squares++;
        }

        $result = self::taylorExp($reduced, $work);

        // Squaring berulang pada work scale; tidak ada pembulatan
        // ke SCALE di antara langkah, sehingga error relatif tetap kecil.
        for ($i = 0; $i < $squares; $i++) {
            $result = bcmul($result, $result, $work);
        }

        // exp(-x) = 1 / exp(x): lakukan setelah ekspansi positif,
        // bukan dengan mengekspansi nilai kecil (yang kehilangan digit).
        if ($negative) {
            $result = bcdiv('1', $result, $work);
        }

        return new self(
            Decimal::normalize(
                Decimal::round($result, self::SCALE)
            )
        );
    }

    private static function taylorExp(
        string $x,
        int $scale
    ): string {
        $term = '1';
        $sum = '1';

        // Untuk |x| <= 1, term_n = x^n / n! < 1/n!, sehingga n! > 10^scale
        // menjamin term menjadi nol pada scale ini. Batas bawah 400
        // memberi margin besar untuk scale kecil; loop break sendiri
        // saat Decimal::isZero($term) true.
        $maxIterations = max(400, $scale);

        for ($n = 1; $n <= $maxIterations; $n++) {
            $term = bcdiv(
                bcmul($term, $x, $scale),
                (string) $n,
                $scale
            );

            if (Decimal::isZero($term)) {
                break;
            }

            $sum = bcadd($sum, $term, $scale);
        }

        return $sum;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function type(): string
    {
        return $this->isIntegerLike() ? 'integer' : 'decimal';
    }

    public function isIntegerLike(): bool
    {
        return !str_contains($this->value, '.')
            || trim(
                substr(
                    $this->value,
                    strpos($this->value, '.') + 1
                ),
                '0'
            ) === '';
    }

    public function isDecimalLike(): bool
    {
        return !$this->isIntegerLike();
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function integerString(): string
    {
        $normalized = Decimal::normalize($this->value);

        [$integer, $fraction] = array_pad(
            explode('.', $normalized, 2),
            2,
            ''
        );

        if (trim($fraction, '0') !== '') {
            throw new InvalidArgumentException(
                'Modulo requires integer values.'
            );
        }

        return $integer;
    }
}