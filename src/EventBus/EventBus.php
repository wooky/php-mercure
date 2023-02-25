<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\EventBus;

use Psr\Log\LoggerInterface;
use Yakov\PhpMercure\Exception\InvalidRequestException;
use Yakov\PhpMercure\PhpMercureConfig;

/**
 * @internal
 */
final class EventBus implements EventBusInterface
{
    private const HOST = '127.255.255.255';

    private PhpMercureConfig $config;
    private LoggerInterface $logger;
    /** @var callable(int, int, int): ?SocketInterface */ private $socketCreator;

    /**
     * @param ?callable(int, int, int): ?SocketInterface $socketCreator
     *
     * TODO replace $socketCreator with `Socket::create(...)` once targeting PHP 8.1+.
     */
    public function __construct(
        PhpMercureConfig $config,
        LoggerInterface $logger,
        $socketCreator = null
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->socketCreator = $socketCreator ?? [Socket::class, 'create'];
    }

    public function post(Publication $publication): bool
    {
        $sock = $this->bind();
        if (!$sock) {
            return false;
        }
        $payload = serialize($publication);
        if (\strlen($payload) > $this->config->eventBusMaxPayload) {
            throw self::payloadTooBigException();
        }
        $result = $sock->sendto($payload, \strlen($payload), 0, self::HOST, $this->config->eventBusPort);
        $this->logger->debug('Posted publication', [
            'payload' => $payload,
            'result' => $result,
        ]);

        return null !== $result;
    }

    public function get()
    {
        $sock = $this->bind();
        if (!$sock) {
            return;
        }

        $this->logger->debug('Listening for events');
        while ($sock->shouldReceive()) {
            $message = $sock->recvfrom($this->config->eventBusMaxPayload, 0);
            if (null === $message) {
                $this->logger->error('Unable to receive data from socket');

                continue;
            }
            $this->logger->debug('Received data', [
                'data' => $message,
            ]);
            $publication = @unserialize($message, ['allowed_classes' => [Publication::class]]);
            if (!$publication instanceof Publication) {
                $this->logger->error('Received data from socket that is not a Publication');

                continue;
            }
            $this->logger->debug('Parsed publication', [
                'publication' => $publication,
            ]);

            yield $publication;
        }
    }

    /**
     * @internal
     */
    public static function payloadTooBigException(): \Exception
    {
        return new InvalidRequestException('Payload is too big');
    }

    /**
     * @codeCoverageIgnore
     */
    private function bind(): ?SocketInterface
    {
        $sock = ($this->socketCreator)(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$sock) {
            $this->logger->error('Failed to create listening socket', [
                'socketLastError' => socket_last_error(),
            ]);

            return null;
        }
        if (!$sock->setOption(SOL_SOCKET, SO_REUSEADDR, 1)) {
            $this->logger->error('Failed to set socket reuse option');

            return null;
        }
        if (!$sock->setOption(SOL_SOCKET, SO_BROADCAST, 1)) {
            $this->logger->error('Failed to set socket broadcast option');

            return null;
        }
        if (!$sock->bind(self::HOST, $this->config->eventBusPort)) {
            $this->logger->error('Failed to bind socket');

            return null;
        }

        return $sock;
    }
}
