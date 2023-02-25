<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Subscription;

use Psr\Log\LoggerInterface;
use Yakov\PhpMercure\Auth\AuthorizationInspector;
use Yakov\PhpMercure\Auth\AuthorizationInspectorInterface;
use Yakov\PhpMercure\Auth\InspectorParameters;
use Yakov\PhpMercure\EventBus\EventBus;
use Yakov\PhpMercure\EventBus\EventBusInterface;
use Yakov\PhpMercure\Exception\ForbiddenException;
use Yakov\PhpMercure\PhpMercureConfig;
use Yakov\PhpMercure\Topic\Topic;
use Yakov\PhpMercure\Topic\TopicAggregator;
use Yakov\PhpMercure\Util\ParameterExtractor;

class SubscriptionHandler
{
    private const PARAM_TOPIC = 'topic';
    private const PARAM_AUTHORIZATION = 'authorization';

    /** @psalm-readonly */ private PhpMercureConfig $config;
    /** @psalm-readonly */ private AuthorizationInspectorInterface $authorizationInspector;
    /** @psalm-readonly */ private EventBusInterface $eventBus;
    /** @psalm-readonly */ private LoggerInterface $logger;

    public function __construct(
        PhpMercureConfig $config,
        LoggerInterface $logger,
        ?AuthorizationInspectorInterface $authorizationInspector = null,
        ?EventBusInterface $eventBus = null
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->authorizationInspector = $authorizationInspector ?? new AuthorizationInspector();
        $this->eventBus = $eventBus ?? new EventBus($config, $logger);
    }

    public function handleSubscriptionRequest(
        string $query,
        ?string $authorizationHeader,
        ?string $cookies
    ): SubscriptionStreamingResponse {
        $extractor = new ParameterExtractor($query);
        $topicParam = $extractor->getAtLeastOneValue(self::PARAM_TOPIC);
        $authorizationParam = $extractor->getZeroOrOneValue(self::PARAM_AUTHORIZATION);

        $inspectorParameters = new InspectorParameters($this->config->subscriberJwtKey, InspectorParameters::SUBSCRIBE);
        $authorizedTopics = $this->authorizationInspector->getAuthorizedTopics(
            $authorizationHeader,
            $authorizationParam,
            $cookies,
            $inspectorParameters
        );

        $requestedTopics = TopicAggregator::aggregateTopics($topicParam);

        /** @var Topic[] */ $subscriptionAuthorizedTopics = [];
        foreach ($requestedTopics as $topic) {
            foreach ($authorizedTopics as $claim) {
                array_push($subscriptionAuthorizedTopics, ...$topic->getTopicsAuthorizedForClaim($claim));
            }
        }
        $subscriptionUnauthorizedTopics = array_values(array_udiff(
            $requestedTopics,
            $subscriptionAuthorizedTopics,
            fn (Topic $a, Topic $b) => ($a === $b) ? 0 : -1
        ));
        if ($subscriptionUnauthorizedTopics && $this->config->mandatoryAuthorization) {
            throw self::notAuthorizedForAllTopicsException();
        }

        /**
         * TODO.
         *
         * @psalm-suppress MixedArgumentTypeCoercion
         */
        return new SubscriptionStreamingResponse(
            $this->eventBus,
            $subscriptionAuthorizedTopics,
            $subscriptionUnauthorizedTopics,
            $this->logger
        );
    }

    /**
     * @internal
     */
    public static function notAuthorizedForAllTopicsException(): \Exception
    {
        return new ForbiddenException('You must be authorized to all subscribed topics');
    }
}
