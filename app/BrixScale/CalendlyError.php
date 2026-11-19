<?php
declare(strict_types=1);

namespace App\BrixScale;

/** A failed Calendly API call. $status is the HTTP status (0 when Calendly could not be reached). */
final class CalendlyError extends \RuntimeException
{
    public function __construct(string $message, public readonly int $status, public readonly array $details = [])
    {
        parent::__construct($message, $status);
    }
}
