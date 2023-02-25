<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\EventBus;

/**
 * @internal
 */
interface SocketInterface
{
    /**
     * @param array|int|string $value
     */
    public function setOption(int $level, int $option, $value): bool;

    public function bind(string $address, int $port = 0): bool;

    public function sendto(string $data, int $length, int $flags, string $address, int $port = 0): ?int;

    public function recvfrom(int $length, int $flags): ?string;

    public function shouldReceive(): bool;
}
