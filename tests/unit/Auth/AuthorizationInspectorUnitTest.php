<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Auth;

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use PHPUnit\Framework\TestCase;
use Yakov\PhpMercure\Exception\InvalidRequestException;
use Yakov\PhpMercure\Topic\SpecificTopic;

/**
 * TODO.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @internal
 */
final class AuthorizationInspectorUnitTest extends TestCase
{
    private InspectorParameters $inspectorParameters;
    private Sha256 $algorithm;
    private InMemory $key;
    private AuthorizationInspector $authorizationInspector;

    private string $authorizationHeaderJwt;
    private string $authorizationParamJwt;
    private string $cookieJwt;

    /**
     * @param int|string $dataName
     *
     * TODO
     *
     * @psalm-suppress InternalMethod
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->inspectorParameters = new InspectorParameters(
            'MyTestingKey12345^&*()!!!!!AAAAAA!!!!!',
            InspectorParameters::SUBSCRIBE
        );
        $this->algorithm = new Sha256();
        $this->key = InMemory::plainText($this->inspectorParameters->key);
        $this->authorizationInspector = new AuthorizationInspector();

        $this->authorizationHeaderJwt = $this->generateGoodJwt('header');
        $this->authorizationParamJwt = $this->generateGoodJwt('param');
        $this->cookieJwt = $this->generateGoodJwt('cookie');

        parent::__construct($name, $data, $dataName);
    }

    /**
     * @return list{?string, ?string, ?string, SpecificTopic[]}[]
     */
    public function provideAuthorizedTopicsArgumentsWithGoodJwts(): array
    {
        $authorizationHeader = "Bearer {$this->authorizationHeaderJwt}";

        return [
            [$authorizationHeader, $this->authorizationParamJwt, $this->cookieJwt, [new SpecificTopic('header')]],
            [$authorizationHeader, $this->authorizationParamJwt, null, [new SpecificTopic('header')]],
            [$authorizationHeader, null, $this->cookieJwt, [new SpecificTopic('header')]],
            [$authorizationHeader, null, null, [new SpecificTopic('header')]],
            [null, $this->authorizationParamJwt, $this->cookieJwt, [new SpecificTopic('param')]],
            [null, $this->authorizationParamJwt, null, [new SpecificTopic('param')]],
            [null, null, $this->cookieJwt, [new SpecificTopic('cookie')]],
            [null, null, null, []],
        ];
    }

    /**
     * @dataProvider provideAuthorizedTopicsArgumentsWithGoodJwts
     */
    public function testGoodJwts(
        ?string $authorizationHeader,
        ?string $authorizationParam,
        ?string $cookie,
        array $expectedTopics
    ): void {
        $authorizedTopics = $this->authorizationInspector->getAuthorizedTopics(
            $authorizationHeader,
            $authorizationParam,
            $cookie,
            $this->inspectorParameters
        );
        static::assertEquals($expectedTopics, $authorizedTopics);
    }

    public function testBadJwt(): void
    {
        $badJwt = 'bogus';
        $this->expectException(InvalidRequestException::class);
        $this->authorizationInspector->getAuthorizedTopics(null, null, $badJwt, $this->inspectorParameters);
    }

    /**
     * @return list{string, mixed}[]
     */
    public function provideJwtsWithMissingClaims(): array
    {
        return [
            ['bogus', [$this->inspectorParameters->claim => ['topic']]],
            [AuthorizationInspector::SUPER_CLAIM, 'bogus'],
            [AuthorizationInspector::SUPER_CLAIM, ['bogus' => ['topic']]],
            [AuthorizationInspector::SUPER_CLAIM, [$this->inspectorParameters->claim => 'bogus']],
        ];
    }

    /**
     * @dataProvider provideJwtsWithMissingClaims
     *
     * @param mixed $claimValue
     */
    public function testMissingClaimFromJwt(string $claimName, $claimValue): void
    {
        $badJwt = $this->generateJwt($claimName, $claimValue);
        $this->expectExceptionObject(
            AuthorizationInspector::jwtMissingClaimException($this->inspectorParameters->claim)
        );
        $this->authorizationInspector->getAuthorizedTopics(null, null, $badJwt, $this->inspectorParameters);
    }

    /**
     * @return list{mixed}[]
     */
    public function provideJwtsWithNonStringClaimValues(): array
    {
        return [[null], [123], [['topic']]];
    }

    /**
     * @dataProvider provideJwtsWithNonStringClaimValues
     *
     * @param mixed $topic
     */
    public function testJwtsWithNonStringClaimValues($topic): void
    {
        $badJwt = $this->generateGoodJwt($topic);
        $this->expectExceptionObject(
            AuthorizationInspector::jwtClaimMalformedException($this->inspectorParameters->claim)
        );
        $this->authorizationInspector->getAuthorizedTopics(null, null, $badJwt, $this->inspectorParameters);
    }

    public function testBadAuthorizationHeader(): void
    {
        $badAuthorizationHeader = "Bogus {$this->authorizationHeaderJwt}";
        $this->expectExceptionObject(AuthorizationInspector::invalidAuthorizationHeaderException());
        $this->authorizationInspector
            ->getAuthorizedTopics($badAuthorizationHeader, null, null, $this->inspectorParameters)
        ;
    }

    /**
     * @param mixed $topic
     */
    private function generateGoodJwt($topic): string
    {
        return $this->generateJwt(AuthorizationInspector::SUPER_CLAIM, [
            $this->inspectorParameters->claim => [$topic],
        ]);
    }

    /**
     * @param mixed $claimValue
     */
    private function generateJwt(string $claimName, $claimValue): string
    {
        return (new Builder(new JoseEncoder(), ChainedFormatter::default()))
            ->withClaim($claimName, $claimValue)
            ->getToken($this->algorithm, $this->key)
            ->toString()
        ;
    }
}
