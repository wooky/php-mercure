<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Subscription;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yakov\PhpMercure\Auth\AuthorizationInspectorInterface;
use Yakov\PhpMercure\Auth\InspectorParameters;
use Yakov\PhpMercure\EventBus\EventBusInterface;
use Yakov\PhpMercure\Exception\InvalidRequestException;
use Yakov\PhpMercure\PhpMercureConfig;
use Yakov\PhpMercure\Topic\SpecificTopic;

/**
 * @internal
 */
final class SubscriptionHandlerUnitTest extends TestCase
{
    private const SUBSCRIBER_JWT_KEY = 'subscriberJwtKey';

    private InspectorParameters $inspectorParameters;
    /** @var AuthorizationInspectorInterface&MockObject */ private $mockAuthorizationInspector;
    /** @var EventBusInterface&MockObject */ private $mockEventBus;
    private LoggerInterface $logger;
    private SubscriptionHandler $handler;

    protected function setUp(): void
    {
        $config = new PhpMercureConfig('bogus', self::SUBSCRIBER_JWT_KEY);
        $this->inspectorParameters = new InspectorParameters(self::SUBSCRIBER_JWT_KEY, InspectorParameters::SUBSCRIBE);
        $this->mockAuthorizationInspector = $this->createMock(AuthorizationInspectorInterface::class);
        $this->mockEventBus = $this->createMock(EventBusInterface::class);
        $this->logger = new NullLogger();
        $this->handler =
            new SubscriptionHandler($config, $this->logger, $this->mockAuthorizationInspector, $this->mockEventBus);
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

        $expectedResponse =
            new SubscriptionStreamingResponse($this->mockEventBus, [new SpecificTopic($topic)], [], $this->logger);
        $actualResponse = $this->handler->handleSubscriptionRequest($query, null, null);
        static::assertEquals($expectedResponse, $actualResponse);
    }

    public function testWithVerboseQuery(): void
    {
        $authorizedTopic = 'authorizedTopic';
        $unauthorizedTopic = 'unauthorizedTopic';
        $authorizationParam = 'authorizationParam';
        $query = "topic={$authorizedTopic}&topic={$unauthorizedTopic}&authorization={$authorizationParam}";
        $authorizationHeader = 'authorizationHeader';
        $cookie = 'cookie';

        $this->mockAuthorizationInspector
            ->expects(static::once())
            ->method('getAuthorizedTopics')
            ->with($authorizationHeader, $authorizationParam, $cookie, $this->inspectorParameters)
            ->willReturn([new SpecificTopic($authorizedTopic), new SpecificTopic('moreTopics')])
        ;

        $expectedResponse = new SubscriptionStreamingResponse(
            $this->mockEventBus,
            [new SpecificTopic($authorizedTopic)],
            [new SpecificTopic($unauthorizedTopic)],
            $this->logger
        );
        $actualResponse = $this->handler->handleSubscriptionRequest($query, $authorizationHeader, $cookie);
        static::assertEquals($expectedResponse, $actualResponse);
    }

    public function testNotAuthorizedForAllTopics(): void
    {
        $config = new PhpMercureConfig('bogus', self::SUBSCRIBER_JWT_KEY, true);
        $handler =
            new SubscriptionHandler($config, $this->logger, $this->mockAuthorizationInspector, $this->mockEventBus);

        $topic = 'mytopic';
        $query = "topic={$topic}&topic=anotherTopic";
        $this->mockAuthorizationInspector
            ->expects(static::once())
            ->method('getAuthorizedTopics')
            ->with(null, null, null, $this->inspectorParameters)
            ->willReturn([new SpecificTopic($topic)])
        ;

        $this->expectExceptionObject(SubscriptionHandler::notAuthorizedForAllTopicsException());
        $handler->handleSubscriptionRequest($query, null, null);
    }

    /**
     * @return list{string}[]
     */
    public static function provideMalformedQueries(): array
    {
        return [
            [''],
            ['bogus'],
            ['authorization=auth1'],
            ['topic=topic&topic=topic'],
            ['topic=topic&authorization=auth1&authorization=auth2'],
        ];
    }

    /**
     * @dataProvider provideMalformedQueries
     */
    public function testMalformedQueries(string $query): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->handler->handleSubscriptionRequest($query, null, null);
    }
}
