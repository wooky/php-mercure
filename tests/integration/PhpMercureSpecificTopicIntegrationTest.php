<?php

declare(strict_types=1);

namespace Yakov\PhpMercure;

use Amp\Parallel\Worker\DefaultPool;
use Amp\Parallel\Worker\Pool;
use Amp\PHPUnit\AsyncTestCase;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yakov\PhpMercure\Auth\InspectorParameters;
use Yakov\PhpMercure\Publication\PublicationHandler;
use Yakov\PhpMercure\Subscription\OutputInterface;
use Yakov\PhpMercure\Subscription\SubscriptionHandler;

/**
 * @internal
 */
final class PhpMercureSpecificTopicIntegrationTest extends AsyncTestCase
{
    use OutputExpector;

    private const KEY = 'MyTestingKey12345^&*()!!!!!AAAAAA!!!!!';

    private static Sha256 $algorithm;
    private static InMemory $key;

    private PhpMercureConfig $config;
    private LoggerInterface $logger;

    private Pool $workerPool;
    /** @var MockObject&OutputInterface */ private $mockOutput1;
    /** @var MockObject&OutputInterface */ private $mockOutput2;
    /** @var MockObject&OutputInterface */ private $mockOutput3;
    private SubscriptionTask $task1;
    private SubscriptionTask $task2;
    private SubscriptionTask $task3;

    private PublicationHandler $publisher;

    public static function setUpBeforeClass(): void
    {
        self::$algorithm = new Sha256();
        self::$key = InMemory::plainText(self::KEY);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new PhpMercureConfig(self::KEY, self::KEY);
        $this->logger = new Logger('asdf', [new StreamHandler('php://stdout')]);

        $this->workerPool = new DefaultPool();
        [$this->mockOutput1, $subscriber1] = $this->createOutputAndSubscriber();
        $this->task1 = new SubscriptionTask($subscriber1->handleSubscriptionRequest(
            self::createQuery('1', 'bogus'),
            'Bearer ' . self::createJwt(InspectorParameters::SUBSCRIBE, 'authorized1'),
            null
        ));

        [$this->mockOutput2, $subscriber2] = $this->createOutputAndSubscriber();
        $this->task2 = new SubscriptionTask($subscriber2->handleSubscriptionRequest(
            self::createQuery('2', self::createJwt(InspectorParameters::SUBSCRIBE, 'authorized2')),
            null,
            null
        ));

        [$this->mockOutput3, $subscriber3] = $this->createOutputAndSubscriber();
        $this->task3 = new SubscriptionTask($subscriber3->handleSubscriptionRequest(
            self::createQuery('2', null),
            null,
            self::createJwt(InspectorParameters::SUBSCRIBE, 'authorized2')
        ));

        $this->publisher = new PublicationHandler($this->config, $this->logger, null, null);
    }

    protected function tearDown(): void
    {
        $this->workerPool->kill();
    }

    public function testPublishForSingleSubscriber()
    {
        yield $this->workerPool->enqueue($this->task1);
        // yield $this->workerPool->enqueue($this->task2);
        // yield $this->workerPool->enqueue($this->task3);

        // $this->expectOutputToBeCalled($this->mockOutput1, ["data: mydata\n", "id: myid\n", "\n"]);
        // $this->expectOutputToBeCalled($this->mockOutput2, []);
        // $this->expectOutputToBeCalled($this->mockOutput3, []);

        // $this->publisher->handlePublicationRequest(
        //     'topic=authorized1&id=myid&data=mytopic',
        //     'Bearer ' . self::createJwt(InspectorParameters::PUBLISH, 'authorized1'),
        //     null
        // );
        // usleep(250000);
    }

    /**
     * @return list{MockObject&OutputInterface, SubscriptionHandler}
     */
    private function createOutputAndSubscriber(): array
    {
        $mockOutput = $this->createMock(OutputInterface::class);
        $subscriber = new SubscriptionHandler($this->config, $this->logger, null, null, $mockOutput);

        return [$mockOutput, $subscriber];
    }

    private static function createQuery(string $topicNumber, ?string $authorization): string
    {
        $query = "topic=authorized{$topicNumber}&topic=unauthorized{$topicNumber}&" .
            'topic=authorizedCommon&topic=unauthorizedCommon';
        if ($authorization) {
            $query .= "&authorization={$authorization}";
        }

        return $query;
    }

    /**
     * @param InspectorParameters::* $kind
     */
    private static function createJwt(string $kind, string $topic): string
    {
        return (new Builder(new JoseEncoder(), ChainedFormatter::default()))
            ->withClaim('mercure', [$kind => [$topic, 'authorizedCommon']])
            ->getToken(self::$algorithm, self::$key)
            ->toString()
        ;
    }
}
