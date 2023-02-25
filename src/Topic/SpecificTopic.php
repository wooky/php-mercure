<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Topic;

/**
 * @internal
 */
final class SpecificTopic extends Topic
{
    private string $topic;

    public function __construct(string $topic)
    {
        $this->topic = $topic;
    }

    /**
     * {@inheritdoc}
     */
    public function isTopicAuthorizedForClaim(string $topic): bool
    {
        return $topic === $this->topic;
    }

    /**
     * {@inheritdoc}
     */
    public function getTopicsAuthorizedForClaim(Topic $claim): array
    {
        return $claim->isTopicAuthorizedForClaim($this->topic) ? [$this] : [];
    }
}
