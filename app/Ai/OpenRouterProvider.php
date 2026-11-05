<?php
declare(strict_types=1);

namespace App\Ai;

/** Any model on OpenRouter, through its streaming chat-completions endpoint. */
final class OpenRouterProvider implements ChatProvider
{
    use StreamsChatCompletions;

    private const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(
        private string $apiKey,
        private string $model,
        private string $fallbackModel,
        private float $temperature,
        private int $maxTokens,
        private string $siteUrl,
        private string $siteName,
    ) {
    }

    public function name(): string
    {
        return 'openrouter';
    }

    public function stream(string $system, array $messages, callable $onText, ?callable $onReset = null): array
    {
        $body = [
            'model'       => $this->model,
            'messages'    => array_merge([['role' => 'system', 'content' => $system]], $messages),
            'stream'      => true,
            'temperature' => $this->temperature,
            'max_tokens'  => $this->maxTokens,
            'usage'       => ['include' => true],
        ];
        if ($this->fallbackModel !== '' && $this->fallbackModel !== $this->model) {
            // OpenRouter tries these in order when the first is down or rate-limited.
            $body['models'] = [$this->model, $this->fallbackModel];
        }

        return $this->streamChatCompletions(self::ENDPOINT, [
            'Authorization: Bearer ' . $this->apiKey,
            'HTTP-Referer: ' . $this->siteUrl,
            'X-Title: ' . $this->siteName,
        ], $body, 'OpenRouter', $onText);
    }
}
