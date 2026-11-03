<?php
declare(strict_types=1);

namespace App\Ai;

use Anthropic\Beta\Messages\BetaFallbackBlock;
use Anthropic\Beta\Messages\BetaRawContentBlockDeltaEvent;
use Anthropic\Beta\Messages\BetaRawContentBlockStartEvent;
use Anthropic\Beta\Messages\BetaRawMessageDeltaEvent;
use Anthropic\Beta\Messages\BetaRawMessageStartEvent;
use Anthropic\Beta\Messages\BetaTextDelta;
use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;

/** Claude through the official Anthropic PHP SDK. */
final class AnthropicProvider implements ChatProvider
{
    /**
     * Models that accept server-side refusal fallbacks. If Claude declines a
     * request on policy grounds, the API retries it on a fallback model
     * instead of returning an empty refusal. Haiku has no fallback.
     */
    private const FALLBACK_MODELS = ['claude-opus-5-5', 'claude-opus-5', 'claude-sonnet-5-5', 'claude-fable-5-1'];

    public function __construct(
        private string $apiKey,
        private string $model,
        private string $effort,
        private int $maxTokens,
    ) {
    }

    public function name(): string
    {
        return 'anthropic';
    }

    public function stream(string $system, array $messages, callable $onText, ?callable $onReset = null): array
    {
        $client = new Client(apiKey: $this->apiKey, requestOptions: ['timeout' => 120.0, 'maxRetries' => 2]);

        $params = [
            'model'        => $this->model,
            'maxTokens'    => $this->maxTokens,
            'messages'     => $messages,
            // The system prompt only changes when an admin edits products or
            // documents, so it is cached between visitors.
            'system'       => [['type' => 'text', 'text' => $system, 'cacheControl' => ['type' => 'ephemeral']]],
            'outputConfig' => ['effort' => $this->effort],
        ];
        if (in_array($this->model, self::FALLBACK_MODELS, true)) {
            $params['fallbacks'] = 'default';
            $params['betas'] = ['server-side-fallback-2026-07-01'];
        }

        $result = ['model' => $this->model, 'input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'stop_reason' => null];

        try {
            $stream = $client->beta->messages->createStream(...$params);
            foreach ($stream as $event) {
                if ($event instanceof BetaRawMessageStartEvent) {
                    $usage = $event->message->usage;
                    $result['model'] = $event->message->model;
                    $result['input_tokens'] = $usage->inputTokens + (int) $usage->cacheCreationInputTokens + (int) $usage->cacheReadInputTokens;
                    $result['cache_read_tokens'] = (int) $usage->cacheReadInputTokens;
                } elseif ($event instanceof BetaRawContentBlockStartEvent && $event->contentBlock instanceof BetaFallbackBlock) {
                    // The first model declined part-way; its partial text is
                    // superseded by the fallback model's answer that follows.
                    if ($onReset) {
                        $onReset();
                    }
                } elseif ($event instanceof BetaRawContentBlockDeltaEvent && $event->delta instanceof BetaTextDelta) {
                    $onText($event->delta->text);
                } elseif ($event instanceof BetaRawMessageDeltaEvent) {
                    $result['stop_reason'] = $event->delta->stopReason ?? $result['stop_reason'];
                    $result['output_tokens'] = $event->usage->outputTokens;
                }
            }
        } catch (AuthenticationException | PermissionDeniedException $ex) {
            throw new ProviderException('Anthropic rejected the API key: ' . $ex->getMessage(), $ex->status, $ex);
        } catch (RateLimitException $ex) {
            throw new ProviderException('Anthropic rate limit reached: ' . $ex->getMessage(), 429, $ex);
        } catch (APIStatusException $ex) {
            throw new ProviderException('Anthropic API error (' . ($ex->type?->value ?? 'unknown') . '): ' . $ex->getMessage(), $ex->status, $ex);
        } catch (APIConnectionException $ex) {
            throw new ProviderException('Could not reach the Anthropic API: ' . $ex->getMessage(), null, $ex);
        }

        return $result;
    }
}
