<?php
declare(strict_types=1);

namespace App\Ai;

use RuntimeException;

/** A provider call failed. The message is for the admin log, not for visitors. */
final class ProviderException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $status ?? 0, $previous);
    }
}
