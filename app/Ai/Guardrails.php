<?php
declare(strict_types=1);

namespace App\Ai;

/**
 * Catches obvious attempts to override or extract the instructions before
 * any tokens are spent. The system prompt covers paraphrases these miss.
 */
final class Guardrails
{
    private const PATTERNS = [
        '/\bignore\b.{0,30}\b(instructions?|rules?|prompt)\b/i',
        '/\bdisregard\b.{0,30}\b(instructions?|rules?|prompt)\b/i',
        '/\bforget\b.{0,30}\b(instructions?|rules?|prompt)\b/i',
        '/\b(reveal|show|print|repeat|output|dump)\b.{0,30}\b(system\s+prompt|your\s+(prompt|instructions?)|hidden\s+instructions?|context\s+window)\b/i',
        '/\bwhat(\'s|\s+is|\s+are)\s+your\s+(system\s+prompt|instructions?|rules)\b/i',
        '/\b(jailbreak|developer\s+mode|dan\s+mode)\b/i',
        '/\byou\s+are\s+(now|no\s+longer)\b/i',
        '/\bpretend\s+(to\s+be|you\s+are)\b/i',
        '/\bact\s+as\b.{0,30}\b(unrestricted|jailbroken|uncensored|different\s+(ai|assistant))\b/i',
    ];

    public static function isInjection(string $text): bool
    {
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        return false;
    }
}
