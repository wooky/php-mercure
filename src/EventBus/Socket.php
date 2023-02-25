<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\EventBus;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
final class Socket implements SocketInterface
{
    /** @var resource */ private $sock;

    /**
     * @param resource $sock
     */
    private function __construct($sock)
    {
        $this->sock = $sock;
    }

    /**
     * {@inheritdoc}
     * TODO this function is getting called from EventBus constructor. Remove when targeting PHP 8.1+.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function create(int $domain, int $type, int $protocol): ?self
    {
        $sock = socket_create($domain, $type, $protocol);
        if (false === $sock) {
            return null;
        }

        return new self($sock);
    }

    /**
     * {@inheritdoc}
     */
    public function setOption(int $level, int $option, $value): bool
    {
        return socket_set_option($this->sock, $level, $option, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function bind(string $address, int $port = 0): bool
    {
        return @socket_bind($this->sock, $address, $port);
    }

    /**
     * {@inheritdoc}
     */
    public function sendto(string $data, int $length, int $flags, string $address, int $port = 0): ?int
    {
        $result = @socket_sendto($this->sock, $data, $length, $flags, $address, $port);

        return (false !== $result) ? $result : null;
    }

    /**
     * {@inheritdoc}
     */
    public function recvfrom(int $length, int $flags): ?string
    {
        $data = '';
        $address = '';
        $port = 0;
        $result = socket_recvfrom($this->sock, $data, $length, $flags, $address, $port);

        return (false !== $result) ? $data : null;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldReceive(): bool
    {
        return true;
    }
}
