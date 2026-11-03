<?php
declare(strict_types=1);

namespace App\Ai;

/**
 * Used when no API key is configured: builds an answer straight from the
 * retrieved excerpts so the whole chat flow can be tried without an AI account.
 */
final class DemoProvider implements ChatProvider
{
    public function __construct(
        private array $passages,
        private array $products,
        private string $question,
        private string $offTopicReply,
    ) {
    }

    public function name(): string
    {
        return 'demo';
    }

    public function stream(string $system, array $messages, callable $onText, ?callable $onReset = null): array
    {
        $text = $this->compose();
        foreach (preg_split('/(?<=\s)/u', $text) ?: [] as $piece) {
            $onText($piece);
            usleep(12000);
        }
        return ['model' => 'demo', 'input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'stop_reason' => 'end_turn'];
    }

    private function compose(): string
    {
        if (!$this->passages) {
            return $this->offTopicReply . "\n[[brix:app=none;sources=none]]";
        }

        $used = array_slice($this->passages, 0, 3);
        $out = ["Here's what the knowledge base says about that:", ''];
        foreach ($used as $passage) {
            $out[] = '- **' . $passage['title'] . ':** ' . self::excerpt($passage['content']);
        }

        // Same rule as real answers: a card only for a named app, or the tool app when asked for a tool.
        $product = ProductMatcher::named($this->question, $this->products);
        if (!$product && ProductMatcher::wantsTool($this->question)) {
            $product = find_product_by_slug(ProductMatcher::TOOL_SLUG);
            if ($product) {
                $out[] = '';
                $out[] = "If you'd like a tool to set this up, **{$product['name']}** can handle it.";
            }
        }
        $out[] = '';
        $out[] = '_Demo mode: these are raw excerpts. Add an API key in Admin → AI settings for written answers._';
        $out[] = '[[brix:app=' . ($product['slug'] ?? 'none') . ';sources=' . implode(',', range(1, count($used))) . ']]';
        return implode("\n", $out);
    }

    /** The first two sentences of a passage, without markdown clutter. */
    private static function excerpt(string $content): string
    {
        $text = trim((string) preg_replace(['/[#*_>`|]+/', '/\s+/u'], ['', ' '], $content));
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: [$text];
        return str_limit(implode(' ', array_slice($sentences, 0, 2)), 280);
    }
}
