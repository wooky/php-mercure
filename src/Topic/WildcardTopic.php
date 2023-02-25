<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Topic;

/**
 * @internal
 */
final class WildcardTopic extends Topic
{
    /**
     * {@inheritdoc}
     */
    public function isTopicAuthorizedForClaim(string $topic): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTopicsAuthorizedForClaim(Topic $claim): array
    {
        return [$claim];
    }
}
