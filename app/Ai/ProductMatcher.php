<?php
declare(strict_types=1);

namespace App\Ai;

/**
 * Decides which product card, if any, goes under an answer. Answers are
 * informative first: a card shows only for the tool app, when the merchant
 * needs a tool, or for an app the merchant asked about by name.
 */
final class ProductMatcher
{
    /** The app suggested when a merchant needs a tool for what the answer explained. */
    public const TOOL_SLUG = 'brix-convert';

    /** A product the visitor named in the question, if any. */
    public static function named(string $question, array $products): ?array
    {
        $question = mb_strtolower($question);
        foreach ($products as $product) {
            if (str_contains($question, mb_strtolower($product['name']))) {
                return $product;
            }
        }
        return null;
    }

    /** Whether the visitor is asking for a tool, app or plugin. Used by demo mode. */
    public static function wantsTool(string $question): bool
    {
        return (bool) preg_match('/(?<![\p{L}\p{N}])(tool|tools|app|apps|plugin|plugins|software|install|recommend)(?![\p{L}\p{N}])/iu', $question);
    }
}
