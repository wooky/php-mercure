<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Topic;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SpecificTopicUnitTest extends TestCase
{
    private const TOPIC_NAME = 'mytopic';

    private Topic $topic;
    /** @var MockObject&Topic */ private $mockOtherTopic;

    protected function setUp(): void
    {
        $this->topic = new SpecificTopic(self::TOPIC_NAME);
        $this->mockOtherTopic = $this->createMock(Topic::class);
    }

    public function testIsTopicAuthorizedForClaim(): void
    {
        static::assertTrue($this->topic->isTopicAuthorizedForClaim(self::TOPIC_NAME));
        static::assertFalse($this->topic->isTopicAuthorizedForClaim('otherTopic'));
        static::assertFalse($this->topic->isTopicAuthorizedForClaim('*'));
    }

    public function testGetTopicsAuthorizedForClaimIsTrue(): void
    {
        $this->mockOtherTopic->expects(static::once())->method('isTopicAuthorizedForClaim')->willReturn(true);
        $result = $this->topic->getTopicsAuthorizedForClaim($this->mockOtherTopic);
        static::assertEquals([$this->topic], $result);
    }

    public function testGetTopicsAuthorizedForClaimIsFalse(): void
    {
        $this->mockOtherTopic->expects(static::once())->method('isTopicAuthorizedForClaim')->willReturn(false);
        $result = $this->topic->getTopicsAuthorizedForClaim($this->mockOtherTopic);
        static::assertEquals([], $result);
    }
}
