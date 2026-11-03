<?php
declare(strict_types=1);

namespace App\Ai;

interface ChatProvider
{
    /** Short id stored with each answer: anthropic, openrouter or demo. */
    public function name(): string;

    /**
     * Stream a reply. $onText receives each piece of text as it arrives.
     * $onReset is called if the provider throws away what it has streamed so
     * far (a server-side model fallback), so the caller can clear it too.
     *
     * @param list<array{role: string, content: string}> $messages
     * @return array{model: string, input_tokens: int, output_tokens: int, cache_read_tokens: int, stop_reason: ?string}
     */
    public function stream(string $system, array $messages, callable $onText, ?callable $onReset = null): array;
}
