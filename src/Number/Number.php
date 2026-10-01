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
    // 2^14 lebih besar dari batas argumen exp() yang diterima, yaitu 10.000.
    private const MAX_EXP_SQUARINGS = 14;
    private const MAX_EXPONENT = 10000;
    private const MAX_EXP_ARGUMENT = 10000;
    private const NEGATIVE_EXP_ZERO_BOUNDARY = '130';

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
        if (self::isZeroExactString($other->value)) {
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
            self::orderOfCanonical($other->value)
                - self::orderOfCanonical($this->value)
        );

        $scale = self::INTERNAL_SCALE
            + $magnitudeAdjust
            + max(
                self::scaleOfCanonical($this->value),
                self::scaleOfCanonical($other->value)
            );

        // Normalize untuk menghilangkan trailing zero (mis. 1/2 → "0.5"
        // bukan "0.5000...0").
        return new self(
            Decimal::normalize(
                Decimal::div($this->value, $other->value, $scale)
            )
        );
    }

    /** Returns the Euclidean modulo of integers in [0, |$other|). */
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

        if ($exponent === 0) {
            return self::of(1);
        }

        if ($this->value === '1') {
            return $this;
        }

        if ($this->value === '-1') {
            return $exponent % 2 === 0 ? self::of(1) : $this;
        }

        if ($exponent < 0) {
            return $this->one()->div(
                $this->pow(-$exponent)
            );
        }

        if ($exponent === 1 || $this->value === '0') {
            return $this;
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
        $x = $this->value;

        if (str_starts_with($x, '-')) {
            throw new LogicException(
                'Square root requires a non-negative real number.'
            );
        }

        if (self::isZeroExactString($x) || $x === '1') {
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
        $x = $this->value;

        if (self::isZeroExactString($x)) {
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

        if (
            $negative
            && Decimal::compare(
                $absolute,
                self::NEGATIVE_EXP_ZERO_BOUNDARY
            ) >= 0
        ) {
            // e > 1 + 1 + 1/2 + 1/6 + 1/24 + 1/120 > 2.7 dan
            // 2.7^10 > 10^4, sehingga e^-130 < 10^-52, di bawah
            // ambang pembulatan 5 * 10^-(SCALE + 1).
            return self::of(0);
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

        // Heuristic starting precision only; the interval test below certifies correctness.
        $baseWork = max(
            self::EXP_WORK_SCALE,
            self::SCALE + $integerDigits + self::EXP_GUARD
        );
        $iterationBound = 2 * ($baseWork + 32);
        $roundoffEstimate = 16
            * ($iterationBound + self::MAX_EXP_SQUARINGS + 2) ** 2;
        $work = $baseWork
            + self::MAX_EXP_SQUARINGS
            + strlen((string) $roundoffEstimate)
            + 3;

        $work = min($work, Decimal::MAX_INTERNAL_SCALE);

        while (true) {
            [$lower, $upper] = self::positiveExpEnclosure(
                $absolute,
                $integerDigits,
                $work
            );

            if ($negative) {
                $positiveLower = $lower;
                $lower = self::divideLower('1', $upper, $work);
                $upper = self::divideUpper(
                    '1',
                    $positiveLower,
                    $work,
                    self::unitAtScale($work)
                );
            }

            $roundedLower = Decimal::round($lower, self::SCALE);
            $roundedUpper = Decimal::round($upper, self::SCALE);

            if ($roundedLower === $roundedUpper) {
                return new self(Decimal::normalize($roundedLower));
            }

            if ($work > Decimal::MAX_INTERNAL_SCALE - 16) {
                throw new InvalidArgumentException(
                    'exp() could not certify rounding within the maximum supported scale.'
                );
            }

            $work += 16;
        }
    }

    /**
     * @return array{string, string} lower and upper bounds for exp(value)
     */
    private static function positiveExpEnclosure(
        string $value,
        int $integerDigits,
        int $scale
    ): array {
        $unit = self::unitAtScale($scale);
        $reducedLower = $value;
        $reducedUpper = $value;
        $squares = 0;

        while (Decimal::compare($reducedUpper, '1') > 0) {
            if ($squares >= self::MAX_EXP_SQUARINGS) {
                throw new InvalidArgumentException(
                    'exp() argument reduction exceeded its supported bound.'
                );
            }

            $reducedLower = self::divideLower($reducedLower, '2', $scale);
            $reducedUpper = self::divideUpper(
                $reducedUpper,
                '2',
                $scale,
                $unit
            );
            $squares++;
        }

        $targetWidthScale = $scale - 2;
        [$lower, $upper] = self::taylorExpEnclosure(
            $reducedLower,
            $reducedUpper,
            $scale,
            $targetWidthScale,
            $unit
        );

        for ($i = 0; $i < $squares; $i++) {
            $lower = self::multiplyLower($lower, $lower, $scale);
            $upper = self::multiplyUpper($upper, $upper, $scale, $unit);
        }

        return [$lower, $upper];
    }

    /**
     * @return array{string, string} lower and upper bounds for exp(reduced)
     */
    private static function taylorExpEnclosure(
        string $reducedLower,
        string $reducedUpper,
        int $scale,
        int $targetWidthScale,
        string $unit
    ): array {
        $termLower = '1';
        $termUpper = '1';
        $sumLower = '1';
        $sumUpper = '1';
        $maxIterations = 2 * $scale;

        for ($n = 1; $n <= $maxIterations; $n++) {
            $termLower = self::divideLower(
                self::multiplyLower($termLower, $reducedLower, $scale),
                (string) $n,
                $scale
            );
            $termUpper = self::divideUpper(
                self::multiplyUpper($termUpper, $reducedUpper, $scale, $unit),
                (string) $n,
                $scale,
                $unit
            );
            $sumLower = bcadd($sumLower, $termLower, $scale);
            $sumUpper = bcadd($sumUpper, $termUpper, $scale);

            // This interval-width threshold is only a stopping optimization;
            // final rounded-bound equality is the correctness condition.
            // For 0 <= reduced <= 1, the tail after term n is <= term n.
            $upperWithRemainder = bcadd($sumUpper, $termUpper, $scale);
            $width = bcsub($upperWithRemainder, $sumLower, $scale);

            if (
                self::isZeroExactString($width)
                || self::orderOfCanonical($width) < -$targetWidthScale
            ) {
                return [$sumLower, $upperWithRemainder];
            }
        }

        return [$sumLower, bcadd($sumUpper, $termUpper, $scale)];
    }

    private static function multiplyLower(
        string $left,
        string $right,
        int $scale
    ): string {
        return bcmul($left, $right, $scale);
    }

    private static function multiplyUpper(
        string $left,
        string $right,
        int $scale,
        string $unit
    ): string {
        return bcadd(bcmul($left, $right, $scale), $unit, $scale);
    }

    private static function divideLower(
        string $dividend,
        string $divisor,
        int $scale
    ): string {
        return bcdiv($dividend, $divisor, $scale);
    }

    private static function divideUpper(
        string $dividend,
        string $divisor,
        int $scale,
        string $unit
    ): string {
        return bcadd(bcdiv($dividend, $divisor, $scale), $unit, $scale);
    }

    private static function unitAtScale(int $scale): string
    {
        return '0.' . str_repeat('0', $scale - 1) . '1';
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
        return !str_contains($this->value, '.');
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
        if (str_contains($this->value, '.')) {
            throw new InvalidArgumentException(
                'Modulo requires integer values.'
            );
        }

        return $this->value;
    }

    private static function isZeroExactString(string $value): bool
    {
        return trim(str_replace(['-', '.'], '', $value), '0') === '';
    }

    private static function scaleOfCanonical(string $value): int
    {
        $position = strpos($value, '.');

        return $position === false
            ? 0
            : strlen($value) - $position - 1;
    }

    private static function orderOfCanonical(string $value): int
    {
        $value = ltrim($value, '-');
        $dotPosition = strpos($value, '.');

        if ($dotPosition === false) {
            $integer = $value;
            $fraction = '';
        } else {
            $integer = substr($value, 0, $dotPosition);
            $fraction = substr($value, $dotPosition + 1);
        }

        $integer = ltrim($integer, '0');

        if ($integer !== '') {
            return strlen($integer) - 1;
        }

        $firstNonZero = strspn($fraction, '0');

        return $firstNonZero === strlen($fraction)
            ? 0
            : -($firstNonZero + 1);
    }
}