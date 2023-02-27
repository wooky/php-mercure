<?php

declare(strict_types=1);

namespace Yakov\PhpMercure;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yakov\PhpMercure\Subscription\OutputInterface;

trait OutputExpector
{
    /**
     * TODO withConsecutive() is deprecated/removed from PHPUnit with no alternative, so this will have to do.
     * Credit: https://github.com/sebastianbergmann/phpunit/issues/4026#issuecomment-1441880611.
     *
     * @param MockObject&OutputInterface $output
     * @param string[]                   $lines
     *
     * @psalm-suppress InternalMethod
     */
    protected function expectOutputToBeCalled($output, array $lines): void
    {
        $matcher = TestCase::exactly(\count($lines));
        $output
            ->expects($matcher)
            ->method('output')
            ->willReturnCallback(function (string $data) use ($lines, $matcher) {
                $this->assertEquals($lines[$matcher->getInvocationCount() - 1], $data);
            })
        ;
    }
}
