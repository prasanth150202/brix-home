<?php
declare(strict_types=1);

namespace App\Ai;

/** ChatGPT models through OpenAI's streaming chat-completions endpoint. */
final class OpenAiProvider implements ChatProvider
{
    use StreamsChatCompletions;

    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    public function __construct(
        private string $apiKey,
        private string $model,
        private float $temperature,
        private int $maxTokens,
    ) {
    }

    public function name(): string
    {
        return 'openai';
    }

    public function stream(string $system, array $messages, callable $onText, ?callable $onReset = null): array
    {
        $body = [
            'model'                 => $this->model,
            'messages'              => array_merge([['role' => 'system', 'content' => $system]], $messages),
            'stream'                => true,
            'stream_options'        => ['include_usage' => true],
            'max_completion_tokens' => $this->maxTokens,
        ];
        $tuned = $body;
        if (self::isReasoningModel($this->model)) {
            // Reasoning models only accept the default temperature; low effort keeps chat replies quick.
            $tuned['reasoning_effort'] = 'low';
        } else {
            $tuned['temperature'] = $this->temperature;
        }

        $headers = ['Authorization: Bearer ' . $this->apiKey];
        try {
            return $this->streamChatCompletions(self::ENDPOINT, $headers, $tuned, 'OpenAI', $onText);
        } catch (ProviderException $ex) {
            // A model that rejects one of the tuning options still answers without them.
            // The request is refused before any text streams, so nothing has been shown yet.
            if ($ex->status === 400 && preg_match('/temperature|reasoning/i', $ex->getMessage())) {
                return $this->streamChatCompletions(self::ENDPOINT, $headers, $body, 'OpenAI', $onText);
            }
            throw $ex;
        }
    }

    /** o-series and GPT-5 models reason before answering, except the -chat variants. */
    private static function isReasoningModel(string $model): bool
    {
        return (bool) preg_match('/^(o\d|gpt-5)/i', $model) && !str_contains($model, '-chat');
    }
}
