<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Topic;

/**
 * @internal
 */
final class TopicAggregator
{
    /**
     * @param string[] $topics
     *
     * @return Topic[]
     */
    public static function aggregateTopics(array $topics): array
    {
        /** @var Topic[] */ $topicObjects = [];
        foreach ($topics as $topic) {
            if ('*' === $topic) {
                return [new WildcardTopic()];
            }
            $topicObjects[] = new SpecificTopic($topic);
        }

        return $topicObjects;
    }
}
