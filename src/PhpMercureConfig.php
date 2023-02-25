<?php

declare(strict_types=1);

namespace Yakov\PhpMercure;

/**
 * @psalm-immutable
 *
 * @codeCoverageIgnore
 */
class PhpMercureConfig
{
    /** @var non-empty-string */ public string $publisherJwtKey;
    /** @var non-empty-string */ public string $subscriberJwtKey;
    public bool $mandatoryAuthorization;
    public int $eventBusPort;
    public int $eventBusMaxPayload;

    public function __construct(
        string $publisherJwtKey,
        string $subscriberJwtKey,
        bool $mandatoryAuthorization = false,
        int $eventBusPort = 12345,
        int $eventBusMaxPayload = 65000
    ) {
        \assert(!empty($publisherJwtKey));
        \assert(!empty($subscriberJwtKey));

        $this->publisherJwtKey = $publisherJwtKey;
        $this->subscriberJwtKey = $subscriberJwtKey;
        $this->mandatoryAuthorization = $mandatoryAuthorization;
        $this->eventBusPort = $eventBusPort;
        $this->eventBusMaxPayload = $eventBusMaxPayload;
    }
}
