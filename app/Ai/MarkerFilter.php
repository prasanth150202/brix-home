<?php
declare(strict_types=1);

namespace App\Ai;

use Closure;

/**
 * The model ends every reply with a line like
 *   [[brix:app=rewards-progress-bar;sources=1,3]]
 * This filter passes the streamed text through to the visitor but holds back
 * anything that could be the start of that line, and captures it instead.
 */
final class MarkerFilter
{
    private const PREFIX = '[[brix:';

    private string $pending = '';
    private string $emitted = '';
    private ?string $marker = null;

    /** @param Closure(string): void $emit */
    public function __construct(private Closure $emit)
    {
    }

    public function push(string $text): void
    {
        $this->pending .= $text;
        $this->drain(false);
    }

    /** Forget everything so far (the provider restarted its answer). */
    public function reset(): void
    {
        $this->pending = '';
        $this->emitted = '';
        $this->marker = null;
    }

    /** What the visitor has been shown so far. */
    public function text(): string
    {
        return $this->emitted;
    }

    /**
     * Flush what is left and return the clean text and the parsed closing line.
     *
     * @return array{0: string, 1: ?array{app: ?string, sources: ?list<int>}}
     */
    public function finish(): array
    {
        $this->drain(true);
        return [trim($this->emitted), $this->marker === null ? null : self::parse($this->marker)];
    }

    private function drain(bool $final): void
    {
        while ($this->pending !== '') {
            $pos = strpos($this->pending, '[');
            if ($pos === false) {
                $this->out($this->pending);
                $this->pending = '';
                return;
            }
            if ($pos > 0) {
                $this->out(substr($this->pending, 0, $pos));
                $this->pending = substr($this->pending, $pos);
            }

            $head = substr($this->pending, 0, strlen(self::PREFIX));
            if (!str_starts_with(self::PREFIX, $head)) {
                // An ordinary bracket, e.g. a markdown link.
                $this->out('[');
                $this->pending = substr($this->pending, 1);
                continue;
            }
            if (strlen($this->pending) < strlen(self::PREFIX)) {
                if ($final) {
                    $this->pending = ''; // a cut-off marker at the very end
                }
                return;
            }
            $end = strpos($this->pending, ']]');
            if ($end === false) {
                if ($final) {
                    $this->marker = $this->pending;
                    $this->pending = '';
                }
                return;
            }
            $this->marker = substr($this->pending, 0, $end + 2);
            $this->pending = substr($this->pending, $end + 2);
        }
    }

    private function out(string $text): void
    {
        if ($text === '') {
            return;
        }
        $this->emitted .= $text;
        ($this->emit)($text);
    }

    /** @return array{app: ?string, sources: ?list<int>} */
    private static function parse(string $marker): array
    {
        $inner = rtrim(substr($marker, strlen(self::PREFIX)), "] \t\r\n");
        $app = null;
        $sources = null;
        foreach (explode(';', $inner) as $pair) {
            $parts = explode('=', $pair, 2);
            $key = strtolower(trim($parts[0]));
            $value = trim($parts[1] ?? '');
            if ($key === 'app') {
                $app = slugify($value) ?: null;
            } elseif ($key === 'sources') {
                $numbers = array_map('intval', preg_split('/\D+/', $value) ?: []);
                $sources = array_values(array_unique(array_filter($numbers, static fn (int $n) => $n > 0)));
            }
        }
        return ['app' => $app, 'sources' => $sources];
    }
}
