<?php

declare(strict_types=1);

namespace Gauss\Probability;

use InvalidArgumentException;

/** Internal strict identity encoding for arbitrary PHP outcomes. */
final class OutcomeIdentity
{
    private function __construct()
    {
    }

    public static function key(mixed $outcome): string
    {
        return 'outcome:' . self::encode($outcome);
    }

    private static function encode(mixed $outcome): string
    {
        if ($outcome === null) {
            return 'null';
        }
        if (is_bool($outcome)) {
            return 'bool:' . ($outcome ? '1' : '0');
        }
        if (is_int($outcome)) {
            return 'int:' . $outcome;
        }
        if (is_float($outcome)) {
            if (is_nan($outcome) || is_infinite($outcome)) {
                throw new InvalidArgumentException('Only finite scalar values are supported as sample-space outcomes.');
            }

            if ($outcome == 0.0) {
                return 'float:0';
            }

            return 'float:' . bin2hex(pack('E', $outcome));
        }
        if (is_string($outcome)) {
            return 'string:' . strlen($outcome) . ':' . $outcome;
        }
        if (is_object($outcome)) {
            return 'object:' . spl_object_id($outcome);
        }
        if (is_resource($outcome)) {
            return 'resource:' . get_resource_type($outcome) . ':' . get_resource_id($outcome);
        }
        if (is_array($outcome)) {
            return self::encodeArray($outcome);
        }

        throw new InvalidArgumentException('Unsupported sample-space outcome.');
    }

    private static function encodeArray(array $outcome): string
    {
        foreach ($outcome as $key => $value) {
            if (\ReflectionReference::fromArrayElement($outcome, $key) !== null) {
                throw new InvalidArgumentException('Arrays containing references are not supported as outcomes.');
            }
        }

        $keys = array_keys($outcome);
        if (! array_is_list($outcome)) {
            sort($keys, SORT_STRING);
        }

        $parts = [];
        foreach ($keys as $key) {
            $encodedKey = self::encode($key);
            $encodedValue = self::encode($outcome[$key]);
            $parts[] = self::encodeEntry($encodedKey, $encodedValue);
        }

        return 'array:' . (array_is_list($outcome) ? 'list' : 'assoc') . ':' . count($outcome) . ':' . implode('', $parts);
    }

    private static function encodeEntry(string $encodedKey, string $encodedValue): string
    {
        return 'k:' . strlen($encodedKey) . ':' . $encodedKey . ';v:' . strlen($encodedValue) . ':' . $encodedValue . ';';
    }
}
