<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\EventBus;

/**
 * @internal
 *
 * @psalm-immutable
 *
 * @codeCoverageIgnore
 */
final class Publication
{
    /** @var string[] */ public array $topic;
    public ?string $data;
    public ?string $private;
    public string $id;
    public ?string $type;
    public ?string $retry;

    /**
     * @param string[] $topic
     */
    public function __construct(
        array $topic,
        ?string $data,
        ?string $private,
        string $id,
        ?string $type,
        ?string $retry
    ) {
        $this->topic = $topic;
        $this->data = $data;
        $this->private = $private;
        $this->id = $id;
        $this->type = $type;
        $this->retry = $retry;
    }
}
