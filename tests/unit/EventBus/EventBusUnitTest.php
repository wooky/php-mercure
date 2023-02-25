<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\EventBus;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Yakov\PhpMercure\PhpMercureConfig;

/**
 * @internal
 */
final class EventBusUnitTest extends TestCase
{
    private PhpMercureConfig $config;

    /** @var MockObject&SocketInterface */ private $mockSocket;
    /** @var callable(int, int, int):?SocketInterface */ private $mockSocketCreator;
    private EventBus $eventBus;
    private Publication $publication;

    protected function setUp(): void
    {
        $this->config = new PhpMercureConfig('publisherJwtKey', 'subscriberJwtKey');
        $this->mockSocket = $this->createMock(SocketInterface::class);
        $this->mockSocketCreator = fn (): SocketInterface => $this->mockSocket;
        $this->eventBus = new EventBus($this->config, new NullLogger(), $this->mockSocketCreator);
        $this->publication = new Publication(['topic1', 'topic2'], 'data', 'private', 'id', 'type', 'retry');

        $this->mockSocket->expects(static::exactly(2))->method('setOption')->willReturn(true);
        $this->mockSocket->method('shouldReceive')->willReturn(true, false);
    }

    public function testPost(): void
    {
        $this->setMockSocketBind();
        $this->mockSocket->expects(static::once())->method('sendto')->willReturn(123);
        $result = $this->eventBus->post($this->publication);
        static::assertTrue($result);
    }

    public function testPostBindFail(): void
    {
        $this->setMockSocketBind(false);
        $result = $this->eventBus->post($this->publication);
        static::assertFalse($result);
    }

    public function testPostLargePayload(): void
    {
        $config = new PhpMercureConfig('publisherJwtKey', 'subscriberJwtKey', false, 12345, 1);
        $eventBus = new EventBus($config, new NullLogger(), $this->mockSocketCreator);
        $this->expectExceptionObject(EventBus::payloadTooBigException());
        $this->setMockSocketBind();
        $eventBus->post($this->publication);
    }

    public function testPostSendtoFail(): void
    {
        $this->setMockSocketBind();
        $this->mockSocket->expects(static::once())->method('sendto')->willReturn(null);
        $result = $this->eventBus->post($this->publication);
        static::assertFalse($result);
    }

    public function testGet(): void
    {
        $this->setMockSocketBind();
        $this->mockSocket->expects(static::once())->method('recvfrom')->willReturn(serialize($this->publication));
        $generator = $this->eventBus->get();
        static::assertEquals($this->publication, $generator->current());
    }

    public function testGetBindFail(): void
    {
        $this->setMockSocketBind(false);
        $generator = $this->eventBus->get();
        static::assertFalse($generator->valid());
    }

    public function testGetRecvfromFail(): void
    {
        $this->setMockSocketBind();
        $this->mockSocket->expects(static::once())->method('recvfrom')->willReturn(null);
        $generator = $this->eventBus->get();
        static::assertFalse($generator->valid());
    }

    public function testGetMessageNotPublication(): void
    {
        $this->setMockSocketBind();
        $this->mockSocket->expects(static::once())->method('recvfrom')->willReturn('bogus');
        $generator = $this->eventBus->get();
        static::assertFalse($generator->valid());
    }

    private function setMockSocketBind(bool $success = true): void
    {
        $this->mockSocket->expects(static::once())->method('bind')->willReturn($success);
    }
}
