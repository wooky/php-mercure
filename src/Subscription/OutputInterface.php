<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Subscription;

/**
 * @internal
 */
interface OutputInterface
{
    public function output(string $data): void;

    public function flush(): void;
}
