<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Publication;

use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Yakov\PhpMercure\Auth\AuthorizationInspector;
use Yakov\PhpMercure\Auth\AuthorizationInspectorInterface;
use Yakov\PhpMercure\Auth\InspectorParameters;
use Yakov\PhpMercure\EventBus\EventBus;
use Yakov\PhpMercure\EventBus\EventBusInterface;
use Yakov\PhpMercure\EventBus\Publication;
use Yakov\PhpMercure\Exception\ForbiddenException;
use Yakov\PhpMercure\Exception\InvalidRequestException;
use Yakov\PhpMercure\PhpMercureConfig;
use Yakov\PhpMercure\Util\ParameterExtractor;

class PublicationHandler
{
    private const PARAM_TOPIC = 'topic';
    private const PARAM_DATA = 'data';
    private const PARAM_PRIVATE = 'private';
    private const PARAM_ID = 'id';
    private const PARAM_TYPE = 'type';
    private const PARAM_RETRY = 'retry';
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

    public function handlePublicationRequest(string $query, ?string $authorizationHeader, ?string $cookies): string
    {
        $this->logger->debug('Received publication request', [
            'query' => $query,
            'authorizationHeader' => $authorizationHeader,
            'cookies' => $cookies,
        ]);
        $extractor = new ParameterExtractor($query);
        $topicParam = $extractor->getAtLeastOneValue(self::PARAM_TOPIC);
        $dataParam = $extractor->getZeroOrOneValue(self::PARAM_DATA);
        $privateParam = $extractor->getZeroOrOneValue(self::PARAM_PRIVATE);
        $idParam = $extractor->getZeroOrOneValue(self::PARAM_ID);
        $typeParam = $extractor->getZeroOrOneValue(self::PARAM_TYPE);
        $retryParam = $extractor->getZeroOrOneValue(self::PARAM_RETRY);
        $authorizationParam = $extractor->getZeroOrOneValue(self::PARAM_AUTHORIZATION);

        $inspectorParameters = new InspectorParameters($this->config->publisherJwtKey, InspectorParameters::PUBLISH);
        $authorizedTopics = $this->authorizationInspector->getAuthorizedTopics(
            $authorizationHeader,
            $authorizationParam,
            $cookies,
            $inspectorParameters
        );
        foreach ($topicParam as $topic) {
            foreach ($authorizedTopics as $claim) {
                if ($claim->isTopicAuthorizedForClaim($topic)) {
                    continue 2;
                }
            }

            throw self::notAuthorizedForAllTopicsException();
        }

        if (null !== $idParam && '' !== $idParam && '#' === $idParam[0]) {
            throw self::invalidIdException();
        }
        if (!$idParam) {
            $uuid = Uuid::uuid4();
            $idParam = "urn:uuid:{$uuid->toString()}";
        }

        $publication = new Publication($topicParam, $dataParam, $privateParam, $idParam, $typeParam, $retryParam);
        if (!$this->eventBus->post($publication)) {
            throw self::failedPostingException();
        }

        return $idParam;
    }

    /**
     * @internal
     */
    public static function notAuthorizedForAllTopicsException(): \Exception
    {
        return new ForbiddenException('You are not authorized for all of the topics');
    }

    /**
     * @internal
     */
    public static function invalidIdException(): \Exception
    {
        return new InvalidRequestException('id parameter may not start with a #');
    }

    /**
     * @internal
     */
    public static function failedPostingException(): \Exception
    {
        return new \RuntimeException('Failed to post publication!');
    }
}
