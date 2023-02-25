<?php

declare(strict_types=1);

namespace Yakov\PhpMercure\Exception;

/**
 * @codeCoverageIgnore
 */
class ForbiddenException extends \Exception
{
    public function __construct(string $cause)
    {
        parent::__construct($cause);
    }
}
