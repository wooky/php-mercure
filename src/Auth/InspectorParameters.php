<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Auth;

/**
 * @internal
 *
 * @psalm-immutable
 *
 * @codeCoverageIgnore
 */
final class InspectorParameters
{
    public const PUBLISH = 'publish';
    public const SUBSCRIBE = 'subscribe';

    /** @var non-empty-string */ public string $key;
    /** @var non-empty-string */ public string $claim;

    /**
     * @param non-empty-string $key
     * @param static::*        $claim
     */
    public function __construct(
        string $key,
        string $claim
    ) {
        $this->key = $key;
        $this->claim = $claim;
    }
}
