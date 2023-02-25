<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Subscription;

/**
 * @internal
 */
interface FlusherInterface
{
    public function flush(): void;
}
