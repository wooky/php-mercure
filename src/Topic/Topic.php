<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Topic;

/**
 * @internal
 */
abstract class Topic
{
    abstract public function isTopicAuthorizedForClaim(string $topic): bool;

    /**
     * @return self[]
     */
    abstract public function getTopicsAuthorizedForClaim(self $claim): array;
}
