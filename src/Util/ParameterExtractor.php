<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Util;

use Yakov\PhpMercure\Exception\InvalidRequestException;

/**
 * @internal
 */
final class ParameterExtractor
{
    /** @var array<string, string[]> */ private array $result = [];

    public function __construct(string $query)
    {
        foreach (explode('&', $query) as $param) {
            if (!$param) {
                continue;
            }
            $pair = explode('=', $param, 2);
            if (2 !== \count($pair) || !$pair[0]) {
                throw self::malformedQueryStringException();
            }
            [$key, $value] = $pair;

            if (!\array_key_exists($key, $this->result)) {
                $this->result[$key] = [];
            }

            if (\in_array($value, $this->result[$key], true)) {
                throw self::duplicateKeyValueException($key);
            }

            $this->result[$key][] = $value;
        }
    }

    public function getZeroOrOneValue(string $key): ?string
    {
        if (\array_key_exists($key, $this->result)) {
            if (1 !== \count($this->result[$key])) {
                throw self::parameterRepeatedException($key);
            }

            return reset($this->result[$key]);
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getAtLeastOneValue(string $key): array
    {
        if (\array_key_exists($key, $this->result) && \count($this->result[$key]) >= 1) {
            return $this->result[$key];
        }

        throw self::parameterNotProvidedException($key);
    }

    /**
     * @internal
     *
     * @return array<string, string[]>
     */
    public function dumpResult(): array
    {
        return $this->result;
    }

    /**
     * @internal
     */
    public static function malformedQueryStringException(): \Exception
    {
        return new InvalidRequestException('Malformed query string');
    }

    /**
     * @internal
     */
    public static function duplicateKeyValueException(string $key): \Exception
    {
        return new InvalidRequestException("Parameter '{$key}' has duplicate value");
    }

    /**
     * @internal
     */
    public static function parameterRepeatedException(string $key): \Exception
    {
        return new InvalidRequestException("Parameter '{$key}' was repeated");
    }

    /**
     * @internal
     */
    public static function parameterNotProvidedException(string $key): \Exception
    {
        return new InvalidRequestException("Parameter '{$key}' was not provided");
    }
}
