<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\EventBus;

/**
 * @internal
 */
interface EventBusInterface
{
    public function post(Publication $publication): bool;

    /**
     * @return \Generator<Publication>
     */
    public function get();
}
