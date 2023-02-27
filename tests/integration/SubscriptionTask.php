<?php

declare(strict_types=1);

namespace Yakov\PhpMercure;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use Yakov\PhpMercure\Subscription\SubscriptionStreamingResponse;

class SubscriptionTask implements Task
{
    private SubscriptionStreamingResponse $response;

    public function __construct(SubscriptionStreamingResponse $response)
    {
        $this->response = $response;
    }

    public function run(Environment $environment): void
    {
        $this->response->serve();
    }
}
