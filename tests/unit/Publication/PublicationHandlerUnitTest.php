<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Publication;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use Yakov\PhpMercure\Auth\AuthorizationInspectorInterface;
use Yakov\PhpMercure\Auth\InspectorParameters;
use Yakov\PhpMercure\EventBus\EventBusInterface;
use Yakov\PhpMercure\EventBus\Publication;
use Yakov\PhpMercure\Exception\InvalidRequestException;
use Yakov\PhpMercure\PhpMercureConfig;
use Yakov\PhpMercure\Topic\SpecificTopic;

/**
 * @internal
 */
final class PublicationHandlerUnitTest extends TestCase
{
    private const PUBLISHER_JWT_KEY = 'publisherJwtKey';
    private const UUID = 'myUUID';

    private InspectorParameters $inspectorParameters;
    /** @var AuthorizationInspectorInterface&MockObject */ private $mockAuthorizationInspector;
    /** @var EventBusInterface&MockObject */ private $mockEventBus;
    private PublicationHandler $handler;

    protected function setUp(): void
    {
        $config = new PhpMercureConfig(self::PUBLISHER_JWT_KEY, 'bogus');
        $this->inspectorParameters = new InspectorParameters(self::PUBLISHER_JWT_KEY, InspectorParameters::PUBLISH);
        $this->mockAuthorizationInspector = $this->createMock(AuthorizationInspectorInterface::class);
        $this->mockEventBus = $this->createMock(EventBusInterface::class);
        $this->handler =
            new PublicationHandler($config, new NullLogger(), $this->mockAuthorizationInspector, $this->mockEventBus);

        $mockUuid = $this->createStub(UuidInterface::class);
        $mockUuid->method('toString')->willReturn(self::UUID);
        $mockUuidFactory = $this->createStub(UuidFactoryInterface::class);
        $mockUuidFactory->method('uuid4')->willReturn($mockUuid);
        Uuid::setFactory($mockUuidFactory);
    }

    public function testWithMinimalQuery(): void
    {
        $topic = 'mytopic';
        $query = "topic={$topic}";
        $this->mockAuthorizationInspector
            ->expects(static::once())
            ->method('getAuthorizedTopics')
            ->with(null, null, null, $this->inspectorParameters)
            ->willReturn([new SpecificTopic($topic)])
        ;

        $expectedId = 'urn:uuid:' . self::UUID;
        $this->mockEventBus
            ->expects(static::once())
            ->method('post')
            ->with(new Publication([$topic], null, null, $expectedId, null, null))
            ->willReturn(true)
        ;

        $actualId = $this->handler->handlePublicationRequest($query, null, null);
        static::assertEquals($expectedId, $actualId);
    }

    public function testWithVerboseQuery(): void
    {
        $topics = ['topic1', 'topic2', 'topic3'];
        $data = 'mydata';
        $private = 'myprivate';
        $type = 'mytype';
        $retry = 'myretry';
        $authorizationParam = 'authorizationParam';
        $expectedId = 'myid';
        $query =
            "topic={$topics[0]}&topic={$topics[1]}&topic={$topics[2]}&data={$data}&private={$private}" .
            "&id={$expectedId}&type={$type}&retry={$retry}&authorization={$authorizationParam}";
        $authorizationHeader = 'authorizationHeader';
        $cookie = 'cookie';

        $this->mockAuthorizationInspector
            ->expects(static::once())
            ->method('getAuthorizedTopics')
            ->with($authorizationHeader, $authorizationParam, $cookie, $this->inspectorParameters)
            ->willReturn([
                new SpecificTopic($topics[0]),
                new SpecificTopic($topics[1]),
                new SpecificTopic($topics[2]),
                new SpecificTopic('moreTopics')])
        ;
        $this->mockEventBus
            ->expects(static::once())
            ->method('post')
            ->with(new Publication($topics, $data, $private, $expectedId, $type, $retry))
            ->willReturn(true)
        ;

        $actualId = $this->handler->handlePublicationRequest($query, $authorizationHeader, $cookie);
        static::assertEquals($expectedId, $actualId);
    }

    public function testNotAuthorizedForAllTopics(): void
    {
        $query = 'topic=topic1&topic=topic2';
        $this->mockAuthorizationInspector
            ->expects(static::once())
            ->method('getAuthorizedTopics')
            ->willReturn([new SpecificTopic('topic1'), new SpecificTopic('topic3')])
        ;

        $this->mockEventBus->expects(static::never())->method('post');
        $this->expectExceptionObject(PublicationHandler::notAuthorizedForAllTopicsException());
        $this->handler->handlePublicationRequest($query, null, null);
    }

    public function testInvalidId(): void
    {
        $topic = 'mytopic';
        $query = "topic={$topic}&id=#123";
        $this->mockAuthorizationInspector
            ->expects(static::once())
            ->method('getAuthorizedTopics')
            ->willReturn([new SpecificTopic($topic)])
        ;

        $this->mockEventBus->expects(static::never())->method('post');
        $this->expectExceptionObject(PublicationHandler::invalidIdException());
        $this->handler->handlePublicationRequest($query, null, null);
    }

    public function testPostingFailure(): void
    {
        $topic = 'mytopic';
        $query = "topic={$topic}";
        $this->mockAuthorizationInspector
            ->expects(static::once())
            ->method('getAuthorizedTopics')
            ->willReturn([new SpecificTopic($topic)])
        ;

        $this->mockEventBus->expects(static::once())->method('post')->willReturn(false);
        $this->expectExceptionObject(PublicationHandler::failedPostingException());
        $this->handler->handlePublicationRequest($query, null, null);
    }

    /**
     * @return list{string}[]
     */
    public static function provideMalformedQueries(): array
    {
        return [
            [''],
            ['bogus'],
            ['id=1'],
            ['topic=topic&topic=topic'],
            ['topic=topic&data=data1&data=data2'],
            ['topic=topic&private=private1&private=private2'],
            ['topic=topic&id=id1&id=id2'],
            ['topic=topic&type=type1&type=type2'],
            ['topic=topic&retry=retry1&retry=retry2'],
            ['topic=topic&authorization=auth1&authorization=auth2'],
        ];
    }

    /**
     * @dataProvider provideMalformedQueries
     */
    public function testMalformedQueries(string $query): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->handler->handlePublicationRequest($query, null, null);
    }
}
