<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Auth;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Exception as JwtException;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Yakov\PhpMercure\Exception\InvalidRequestException;
use Yakov\PhpMercure\Topic\Topic;
use Yakov\PhpMercure\Topic\TopicAggregator;

/**
 * @internal
 */
final class AuthorizationInspector implements AuthorizationInspectorInterface
{
    public const SUPER_CLAIM = 'mercure';

    /**
     * {@inheritdoc}
     */
    public function getAuthorizedTopics(
        ?string $authorizationHeader,
        ?string $authorizationParam,
        ?string $cookie,
        InspectorParameters $inspectorParameters
    ): array {
        /** @var string[] */ $authorizedTopics = [];
        if ($authorizationHeader) {
            $authorizedTopics =
                $this->getAuthorizedTopicsFromAuthorizationHeader($authorizationHeader, $inspectorParameters);
        } elseif ($authorizationParam) {
            $authorizedTopics = $this->getAuthorizedTopicsFromJwt($authorizationParam, $inspectorParameters);
        } elseif ($cookie) {
            $authorizedTopics = $this->getAuthorizedTopicsFromJwt($cookie, $inspectorParameters);
        }

        return $authorizedTopics;
    }

    /**
     * @internal
     */
    public static function invalidAuthorizationHeaderException(): \Exception
    {
        return new InvalidRequestException('Authorization header must be a Bearer');
    }

    /**
     * @internal
     */
    public static function jwtMissingClaimException(string $claim): \Exception
    {
        return new InvalidRequestException('JWT is missing claim ' . self::SUPER_CLAIM . '.' . $claim);
    }

    /**
     * @internal
     */
    public static function jwtClaimMalformedException(string $claim): \Exception
    {
        return new InvalidRequestException('JWT claim ' . self::SUPER_CLAIM . '.' . $claim . ' is malformed');
    }

    /**
     * @return Topic[]
     */
    private function getAuthorizedTopicsFromAuthorizationHeader(
        string $authorizationHeader,
        InspectorParameters $params
    ): array {
        $fragments = explode(' ', $authorizationHeader);
        if (2 !== \count($fragments) || 'Bearer' !== $fragments[0]) {
            throw self::invalidAuthorizationHeaderException();
        }

        return $this->getAuthorizedTopicsFromJwt($fragments[1], $params);
    }

    /**
     * @return Topic[]
     */
    private function getAuthorizedTopicsFromJwt(string $jwt, InspectorParameters $params): array
    {
        $jwtKey = InMemory::plainText($params->key);

        try {
            $token = (new JwtFacade())->parse(
                $jwt,
                new SignedWith(new Sha256(), $jwtKey),
                new LooseValidAt(SystemClock::fromUTC())
            );
        } catch (JwtException $e) {
            throw new InvalidRequestException($e->getMessage());
        }
        $mercure = $token->claims()->get(self::SUPER_CLAIM);
        if (!\is_array($mercure) || !isset($mercure[$params->claim]) || !\is_array($mercure[$params->claim])) {
            throw self::jwtMissingClaimException($params->claim);
        }

        $topics = [];
        foreach ($mercure[$params->claim] as $topic) {
            if (!\is_string($topic)) {
                throw self::jwtClaimMalformedException($params->claim);
            }
            $topics[] = $topic;
        }

        return TopicAggregator::aggregateTopics($topics);
    }
}
