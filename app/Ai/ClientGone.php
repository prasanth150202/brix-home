<?php
declare(strict_types=1);

namespace App\Ai;

use RuntimeException;

/** The visitor closed the page or pressed stop while an answer was streaming. */
final class ClientGone extends RuntimeException
{
}
