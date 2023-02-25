<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Topic;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WildcardTopicUnitTest extends TestCase
{
    private Topic $topic;
    /** @var MockObject&Topic */ private $mockOtherTopic;

    protected function setUp(): void
    {
        $this->topic = new WildcardTopic();
        $this->mockOtherTopic = $this->createMock(Topic::class);
    }

    public function testIsTopicAuthorizedForClaim(): void
    {
        static::assertTrue($this->topic->isTopicAuthorizedForClaim('mytopic'));
        static::assertTrue($this->topic->isTopicAuthorizedForClaim('otherTopic'));
        static::assertTrue($this->topic->isTopicAuthorizedForClaim('*'));
    }

    public function testGetTopicsAuthorizedForClaim(): void
    {
        $result = $this->topic->getTopicsAuthorizedForClaim($this->mockOtherTopic);
        static::assertEquals([$this->mockOtherTopic], $result);
    }
}
