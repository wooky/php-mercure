<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Subscription;

use Psr\Log\LoggerInterface;
use Yakov\PhpMercure\EventBus\EventBusInterface;
use Yakov\PhpMercure\EventBus\Publication;
use Yakov\PhpMercure\Topic\Topic;

class SubscriptionStreamingResponse
{
    /** @var array<string, string> */ public const HEADERS = [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
    ];

    private EventBusInterface $eventBus;
    /** @var Topic[] */ private array $authorizedTopics;
    /** @var Topic[] */ private array $unauthorizedTopics;
    private LoggerInterface $logger;
    private OutputInterface $output;

    /**
     * @param Topic[] $authorizedTopics   Topics for which the user is authorized
     * @param Topic[] $unauthorizedTopics Topics for which the user is unauthorized
     */
    public function __construct(
        EventBusInterface $eventBus,
        array $authorizedTopics,
        array $unauthorizedTopics,
        LoggerInterface $logger,
        ?OutputInterface $output = null
    ) {
        $this->eventBus = $eventBus;
        $this->authorizedTopics = $authorizedTopics;
        $this->unauthorizedTopics = $unauthorizedTopics;
        $this->logger = $logger;
        $this->output = $output ?? new StandardOutput();
    }

    public function serve(): void
    {
        $this->output->flush();

        foreach ($this->eventBus->get() as $publication) {
            $shouldWrite = self::shouldWrite($publication, $this->authorizedTopics);
            $this->logger->debug('Should write authorized topics?', ['shouldWrite' => $shouldWrite]);
            if (!$shouldWrite && null === $publication->private) {
                $shouldWrite = self::shouldWrite($publication, $this->unauthorizedTopics);
                $this->logger->debug('Should write unauthorized topics?', ['shouldWrite' => $shouldWrite]);
            }

            if ($shouldWrite) {
                $this->writePayload($publication);
            }
        }
    }

    /**
     * @param Topic[] $claims
     */
    private static function shouldWrite(Publication $publication, array $claims): bool
    {
        foreach ($publication->topic as $topic) {
            foreach ($claims as $claim) {
                if ($claim->isTopicAuthorizedForClaim($topic)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function writePayload(Publication $publication): void
    {
        $this->outputIf($publication->type, "event: {$publication->type}\n");
        if ($publication->data) {
            foreach (explode("\n", $publication->data) as $data) {
                $this->output->output("data: {$data}\n");
            }
        }
        $this->output->output("id: {$publication->id}\n");
        $this->outputIf($publication->retry, "retry: {$publication->retry}\n");
        $this->output->output("\n");
        $this->output->flush();
    }

    /**
     * @param mixed $condition
     */
    private function outputIf($condition, string $data): void
    {
        if ($condition) {
            $this->output->output($data);
        }
    }
}
