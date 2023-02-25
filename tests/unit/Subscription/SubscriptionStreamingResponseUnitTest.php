<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Subscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Yakov\PhpMercure\EventBus\EventBusInterface;
use Yakov\PhpMercure\EventBus\Publication;
use Yakov\PhpMercure\Topic\SpecificTopic;

/**
 * @internal
 */
final class SubscriptionStreamingResponseUnitTest extends TestCase
{
    private const AUTHORIZED_TOPIC = 'authorizedTopic';
    private const UNAUTHORIZED_TOPIC = 'unauthorizedTopic';

    private string $publicationId = 'myid';
    /** @var EventBusInterface&MockObject */ private $mockEventBus;
    /** @var MockObject&OutputInterface */ private $mockOutput;
    private SubscriptionStreamingResponse $response;

    protected function setUp(): void
    {
        $this->mockEventBus = $this->createMock(EventBusInterface::class);
        $this->mockOutput = $this->createMock(OutputInterface::class);
        $this->response = new SubscriptionStreamingResponse(
            $this->mockEventBus,
            [new SpecificTopic(self::AUTHORIZED_TOPIC)],
            [new SpecificTopic(self::UNAUTHORIZED_TOPIC)],
            new NullLogger(),
            $this->mockOutput
        );
    }

    public function testWriteMinimalPayload(): void
    {
        $publication =
            new Publication([self::UNAUTHORIZED_TOPIC, 'otherTopic'], null, null, $this->publicationId, null, null);
        $this->setupMockEventBus($publication);
        $this->mockOutput->expects(static::exactly(2))->method('flush');

        $this->expectOutputToBeCalled(["id: {$this->publicationId}\n", "\n"]);
        $this->response->serve();
    }

    public function testWriteLargePayload(): void
    {
        $data = "\ndata1\ndata2\ndata3\n";
        $private = 'myprivate';
        $type = 'mytype';
        $retry = 'myretry';
        $publication = new Publication(
            [self::AUTHORIZED_TOPIC, 'otherTopic'],
            $data,
            $private,
            $this->publicationId,
            $type,
            $retry
        );
        $this->setupMockEventBus($publication);
        $this->mockOutput->expects(static::exactly(2))->method('flush');

        $this->expectOutputToBeCalled([
            "event: {$type}\n",
            "data: \n",
            "data: data1\n",
            "data: data2\n",
            "data: data3\n",
            "data: \n",
            "id: {$this->publicationId}\n",
            "retry: {$retry}\n",
            "\n",
        ]);
        $this->response->serve();
    }

    public function testUnrelatedTopic(): void
    {
        $publication = new Publication(['otherTopic'], null, null, $this->publicationId, null, null);
        $this->setupMockEventBus($publication);
        $this->mockOutput->expects(static::once())->method('flush');

        $this->mockOutput->expects(static::never())->method('output');
        $this->response->serve();
    }

    public function testPrivateTopicWithNoAuthorization(): void
    {
        $publication = new Publication([self::UNAUTHORIZED_TOPIC], null, 'on', $this->publicationId, null, null);
        $this->setupMockEventBus($publication);
        $this->mockOutput->expects(static::once())->method('flush');

        $this->mockOutput->expects(static::never())->method('output');
        $this->response->serve();
    }

    private function setupMockEventBus(Publication $publication): void
    {
        $this->mockEventBus
            ->expects(static::once())
            ->method('get')
            ->willReturnCallback(function () use ($publication) {
                yield $publication;
            })
        ;
    }

    /**
     * TODO withConsecutive() is deprecated/removed from PHPUnit with no alternative, so this will have to do.
     * Credit: https://github.com/sebastianbergmann/phpunit/issues/4026#issuecomment-1441880611.
     *
     * @param string[] $lines
     *
     * @psalm-suppress InternalMethod
     */
    private function expectOutputToBeCalled(array $lines): void
    {
        $matcher = static::exactly(\count($lines));
        $this->mockOutput
            ->expects($matcher)
            ->method('output')
            ->willReturnCallback(function (string $data) use ($lines, $matcher) {
                $this->assertEquals($lines[$matcher->getInvocationCount() - 1], $data);
            })
        ;
    }
}
