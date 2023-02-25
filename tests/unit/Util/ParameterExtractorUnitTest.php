<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Util;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ParameterExtractorUnitTest extends TestCase
{
    private const GOOD_QUERY = 'repeated=1&single=yes&repeated=2&empty=';

    public function testNormalOperation(): void
    {
        $extractor = new ParameterExtractor(self::GOOD_QUERY);
        static::assertEquals('yes', $extractor->getZeroOrOneValue('single'));
        static::assertEquals(['yes'], $extractor->getAtLeastOneValue('single'));
        static::assertEquals(['1', '2'], $extractor->getAtLeastOneValue('repeated'));
        static::assertEquals('', $extractor->getZeroOrOneValue('empty'));
        static::assertNull($extractor->getZeroOrOneValue('unset'));
    }

    public function testEmptyQueryStringIsOk(): void
    {
        $extractor = new ParameterExtractor('');
        static::assertEquals([], $extractor->dumpResult());
    }

    public function testQueryStringWithEmptyKeyValuesIsOk(): void
    {
        $extractor = new ParameterExtractor('&&&&&');
        static::assertEquals([], $extractor->dumpResult());
    }

    /**
     * @return list{string}[]
     */
    public static function provideMalformedQueryStrings(): array
    {
        return [
            ['bogus'],
            ['key=value&bogus'],
            ['='],
        ];
    }

    /**
     * @dataProvider provideMalformedQueryStrings
     */
    public function testMalformedQueryString(string $query): void
    {
        $this->expectExceptionObject(ParameterExtractor::malformedQueryStringException());
        new ParameterExtractor($query);
    }

    public function testDuplicateKeyValue(): void
    {
        $this->expectExceptionObject(ParameterExtractor::duplicateKeyValueException('dupe'));
        new ParameterExtractor('dupe=value&single=yes&dupe=value');
    }

    public function testGetZeroOrOneValueHasMoreThanOneValue(): void
    {
        $key = 'repeated';
        $this->expectExceptionObject(ParameterExtractor::parameterRepeatedException($key));
        $extractor = new ParameterExtractor(self::GOOD_QUERY);
        $extractor->getZeroOrOneValue($key);
    }

    public function testGetAtLeastOneValueIsUnset(): void
    {
        $key = 'unset';
        $this->expectExceptionObject(ParameterExtractor::parameterNotProvidedException($key));
        $extractor = new ParameterExtractor(self::GOOD_QUERY);
        $extractor->getAtLeastOneValue($key);
    }
}
