<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Subscription;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
final class Flusher implements FlusherInterface
{
    public function flush(): void
    {
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
