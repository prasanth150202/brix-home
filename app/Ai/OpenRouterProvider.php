<?php
declare(strict_types=1);

namespace App\Ai;

/** Any model on OpenRouter, through its streaming chat-completions endpoint. */
final class OpenRouterProvider implements ChatProvider
{
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

        $result = ['model' => $this->model, 'input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'stop_reason' => null];
        $buffer = '';
        $errorBody = '';
        $streamError = null;

        $handleLine = function (string $line) use (&$result, &$streamError, $onText): void {
            if (!str_starts_with($line, 'data:')) {
                return; // blank lines and ": OPENROUTER PROCESSING" keep-alives
            }
            $data = trim(substr($line, 5));
            if ($data === '' || $data === '[DONE]') {
                return;
            }
            $json = json_decode($data, true);
            if (!is_array($json)) {
                return;
            }
            if (isset($json['error'])) {
                $streamError = (string) ($json['error']['message'] ?? 'Unknown error');
                return;
            }
            $choice = $json['choices'][0] ?? [];
            $text = $choice['delta']['content'] ?? '';
            if (is_string($text) && $text !== '') {
                $onText($text);
            }
            if (!empty($choice['finish_reason'])) {
                $result['stop_reason'] = (string) $choice['finish_reason'];
            }
            if (!empty($json['model'])) {
                $result['model'] = (string) $json['model'];
            }
            if (isset($json['usage'])) {
                $result['input_tokens'] = (int) ($json['usage']['prompt_tokens'] ?? 0);
                $result['output_tokens'] = (int) ($json['usage']['completion_tokens'] ?? 0);
                $result['cache_read_tokens'] = (int) ($json['usage']['prompt_tokens_details']['cached_tokens'] ?? 0);
            }
        };

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'HTTP-Referer: ' . $this->siteUrl,
                'X-Title: ' . $this->siteName,
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_WRITEFUNCTION  => function ($ch, string $chunk) use (&$buffer, &$errorBody, $handleLine): int {
                if (curl_getinfo($ch, CURLINFO_RESPONSE_CODE) >= 400) {
                    $errorBody .= $chunk;
                    return strlen($chunk);
                }
                $buffer .= $chunk;
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = rtrim(substr($buffer, 0, $pos), "\r");
                    $buffer = substr($buffer, $pos + 1);
                    $handleLine($line);
                }
                return strlen($chunk);
            },
        ]);

        $ok = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($buffer !== '') {
            $handleLine(rtrim($buffer, "\r"));
        }
        if ($ok === false) {
            throw new ProviderException('Could not reach OpenRouter: ' . $curlError);
        }
        if ($status >= 400) {
            $decoded = json_decode($errorBody, true);
            $message = $decoded['error']['message'] ?? trim($errorBody) ?: 'HTTP ' . $status;
            throw new ProviderException("OpenRouter error ($status): $message", $status);
        }
        if ($streamError !== null) {
            throw new ProviderException('OpenRouter stream error: ' . $streamError);
        }
        return $result;
    }
}
