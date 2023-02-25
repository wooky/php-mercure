<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Subscription;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
final class StandardOutput implements OutputInterface
{
    public function output(string $data): void
    {
        echo $data;
    }

    public function flush(): void
    {
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
