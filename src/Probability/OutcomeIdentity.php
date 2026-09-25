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
            if (is_nan($outcome)) {
                throw new InvalidArgumentException('NAN is not a supported sample-space outcome.');
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
            $serialized = serialize($outcome);
            if (preg_match('/(?:^|[;{])[rR]:\d+;/', $serialized) === 1) {
                throw new InvalidArgumentException('Circular or referenced arrays are not supported as outcomes.');
            }

            $parts = [];
            foreach ($outcome as $key => $value) {
                $parts[] = self::encode($key) . '=>' . self::encode($value);
            }

            return 'array:' . implode('|', $parts);
        }

        throw new InvalidArgumentException('Unsupported sample-space outcome.');
    }
}
