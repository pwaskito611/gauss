<?php

declare(strict_types=1);

namespace Gauss\Distribution;

use Gauss\Number\Number;

trait DiscreteIndexSupport
{
    private static function floorIndex(Number $value): int
    {
        $normalized = $value->value();
        $negative = str_starts_with($normalized, '-');
        $digits = ltrim($normalized, '+-');

        if (! str_contains($digits, '.')) {
            $whole = (int) $digits;

            return $negative ? -$whole : $whole;
        }

        [$whole, $fraction] = explode('.', $digits, 2);
        $wholeValue = $whole === '' ? 0 : (int) $whole;

        if ($negative && $fraction !== '' && trim($fraction, '0') !== '') {
            $wholeValue++;
        }

        return $negative ? -$wholeValue : $wholeValue;
    }
}
