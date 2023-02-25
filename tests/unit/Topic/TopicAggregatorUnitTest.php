<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Topic;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class TopicAggregatorUnitTest extends TestCase
{
    public function testAggregateRegularTopics(): void
    {
        $expectedTopics = [new SpecificTopic('topic1'), new SpecificTopic('topic2'), new SpecificTopic('topic3')];
        $actualTopics = TopicAggregator::aggregateTopics(['topic1', 'topic2', 'topic3']);
        static::assertEquals($expectedTopics, $actualTopics);
    }

    public function testWildcardTopicTrumpsAll(): void
    {
        $expectedTopics = [new WildcardTopic()];
        $actualTopics = TopicAggregator::aggregateTopics(['topic1', 'topic2', '*', 'topic3']);
        static::assertEquals($expectedTopics, $actualTopics);
    }
}
