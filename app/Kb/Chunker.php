<?php
declare(strict_types=1);

namespace App\Kb;

/**
 * Splits a document into passages of roughly $target characters. Breaks only
 * between paragraphs (or sentences, for very long paragraphs) and starts a new
 * passage at every heading, so each passage stays about one topic.
 */
final class Chunker
{
    public function __construct(
        private int $target = 1100,
        private int $max = 1600,
        private int $minTail = 250,
    ) {
    }

    /** @return list<array{heading: ?string, content: string}> */
    public function chunk(string $text): array
    {
        $chunks = [];
        $heading = null;
        $buffer = [];
        $length = 0;

        $flush = function () use (&$chunks, &$buffer, &$length, &$heading): void {
            $content = trim(implode("\n\n", $buffer));
            if ($content !== '') {
                // Fold a short tail into the previous passage of the same section.
                $last = count($chunks) - 1;
                if ($last >= 0 && mb_strlen($content) < $this->minTail && $chunks[$last]['heading'] === $heading
                    && mb_strlen($chunks[$last]['content']) + mb_strlen($content) <= $this->max) {
                    $chunks[$last]['content'] .= "\n\n" . $content;
                } else {
                    $chunks[] = ['heading' => $heading, 'content' => $content];
                }
            }
            $buffer = [];
            $length = 0;
        };

        foreach (preg_split("/\n\s*\n/u", $text) as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            // A heading line starts a new section; text right under it stays in the block.
            if (preg_match('/^#{1,6}\s+([^\n]+)(?:\n(.*))?$/su', $block, $m)) {
                $flush();
                $heading = mb_substr(trim($m[1], " #*"), 0, 250);
                $block = trim($m[2] ?? '');
                if ($block === '') {
                    continue;
                }
            }

            foreach ($this->pieces($block) as $piece) {
                $pieceLength = mb_strlen($piece);
                if ($length > 0 && $length + $pieceLength > $this->target) {
                    $flush();
                }
                $buffer[] = $piece;
                $length += $pieceLength + 2;
            }
        }
        $flush();

        return $chunks;
    }

    /** A paragraph, or sentence-sized parts of it when it is too long. */
    private function pieces(string $block): array
    {
        if (mb_strlen($block) <= $this->max) {
            return [$block];
        }
        $sentences = preg_split('/(?<=[.!?])\s+/u', $block) ?: [$block];
        $pieces = [];
        $current = '';
        foreach ($sentences as $sentence) {
            if ($current !== '' && mb_strlen($current) + mb_strlen($sentence) > $this->target) {
                $pieces[] = $current;
                $current = '';
            }
            // A single run-on "sentence" longer than the limit is cut hard.
            while (mb_strlen($sentence) > $this->max) {
                $pieces[] = mb_substr($sentence, 0, $this->target);
                $sentence = mb_substr($sentence, $this->target);
            }
            $current = $current === '' ? $sentence : $current . ' ' . $sentence;
        }
        if ($current !== '') {
            $pieces[] = $current;
        }
        return $pieces;
    }
}
