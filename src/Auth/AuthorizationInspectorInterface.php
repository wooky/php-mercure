<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Auth;

use Yakov\PhpMercure\Topic\Topic;

/**
 * @internal
 */
interface AuthorizationInspectorInterface
{
    /**
     * @return Topic[]
     */
    public function getAuthorizedTopics(
        ?string $authorizationHeader,
        ?string $authorizationParam,
        ?string $cookie,
        InspectorParameters $inspectorParameters
    ): array;
}
