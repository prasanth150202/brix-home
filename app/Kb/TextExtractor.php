<?php
declare(strict_types=1);

namespace App\Kb;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;
use ZipArchive;

/**
 * Turns an uploaded document into plain text. Headings are kept as markdown
 * "## " lines so the chunker can split on them.
 */
final class TextExtractor
{
    public const EXTENSIONS = ['pdf', 'docx', 'txt', 'md', 'markdown', 'html', 'htm'];

    public static function fromFile(string $path, string $ext): string
    {
        $text = match (strtolower($ext)) {
            'pdf' => self::pdf($path),
            'docx' => self::docx($path),
            'html', 'htm' => self::html((string) file_get_contents($path)),
            'txt', 'md', 'markdown' => self::markdown((string) file_get_contents($path)),
            default => throw new RuntimeException("Unsupported file type .$ext"),
        };

        $text = self::normalize($text);
        if ($text === '') {
            throw new RuntimeException('No text could be read from this file. If it is a scanned PDF, export it with selectable text or paste the text instead.');
        }
        return $text;
    }

    /** Clean up pasted or extracted text. */
    public static function normalize(string $text): string
    {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }
        $text = str_replace(["\r\n", "\r", "\u{00A0}", "\u{FEFF}"], ["\n", "\n", ' ', ''], $text);
        $text = (string) preg_replace('/[ \t]+/u', ' ', $text);
        $text = (string) preg_replace('/ *\n */u', "\n", $text);
        $text = (string) preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    /** Drop YAML front matter and markdown noise that would pollute search. */
    public static function markdown(string $md): string
    {
        $md = (string) preg_replace('/\A\xEF\xBB\xBF/', '', $md);
        $md = (string) preg_replace('/\A---\R.*?\R---\R/s', '', $md);
        $md = (string) preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $md);          // images
        $md = (string) preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $md);         // links -> text
        $md = (string) preg_replace('/\\\\([.\-#*_()\[\]!])/', '$1', $md);         // escaped punctuation
        $md = (string) preg_replace('/<[^>]+>/', '', $md);                          // inline html
        return $md;
    }

    private static function pdf(string $path): string
    {
        // Image-heavy PDFs need room to decode; images themselves are not kept.
        $limit = ini_get('memory_limit');
        if ($limit !== '-1' && self::bytes((string) $limit) < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
        $config = new Config();
        $config->setRetainImageContent(false);
        $config->setDecodeMemoryLimit(64 * 1024 * 1024);

        try {
            $pdf = (new Parser([], $config))->parseFile($path);
            $text = $pdf->getText();
        } catch (\Throwable $ex) {
            throw new RuntimeException('Could not read the PDF (' . $ex->getMessage() . '). If it opens fine on your computer, re-save it with "Print to PDF" and upload that copy, or paste the text instead.');
        }
        // Re-join words hyphenated across line breaks, and lines inside a paragraph.
        $text = (string) preg_replace('/(\w)-\n(\w)/u', '$1$2', $text);
        $text = (string) preg_replace('/([^\n.!?:])\n(?=[a-z(])/u', '$1 ', $text);
        return str_replace("\t", ' ', $text);
    }

    private static function bytes(string $value): int
    {
        $number = (int) $value;
        return match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private static function docx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open the Word document.');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            throw new RuntimeException('This .docx file has no document body.');
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $out = [];
        foreach ($xp->query('//w:body//w:p') as $p) {
            $text = '';
            foreach ($xp->query('.//w:t|.//w:tab|.//w:br', $p) as $node) {
                $text .= match ($node->localName) {
                    't' => $node->textContent,
                    'tab' => ' ',
                    default => "\n",
                };
            }
            $text = trim($text);
            if ($text === '') {
                continue;
            }
            $style = (string) $xp->evaluate('string(w:pPr/w:pStyle/@w:val)', $p);
            if (preg_match('/^(?:Heading|heading)\s*([1-6])$/', $style, $m) || $style === 'Title') {
                $level = isset($m[1]) ? max(1, min(6, (int) $m[1])) : 1;
                $text = str_repeat('#', $level) . ' ' . $text;
            } elseif ($xp->query('w:pPr/w:numPr', $p)->length > 0) {
                $text = '- ' . $text;
            }
            $out[] = $text;
        }
        return implode("\n\n", $out);
    }

    private static function html(string $html): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        foreach (['script', 'style', 'noscript', 'nav', 'footer', 'header', 'form', 'svg'] as $tag) {
            foreach (iterator_to_array($dom->getElementsByTagName($tag)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }
        $body = $dom->getElementsByTagName('body')->item(0) ?? $dom->documentElement;
        return $body ? self::walk($body) : '';
    }

    private static function walk(DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $out .= preg_replace('/\s+/u', ' ', $child->textContent);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            $inner = self::walk($child);
            $out .= match (true) {
                (bool) preg_match('/^h([1-6])$/', $tag, $m) => "\n\n" . str_repeat('#', (int) $m[1]) . ' ' . trim($inner) . "\n\n",
                $tag === 'li' => "\n- " . trim($inner),
                $tag === 'br' => "\n",
                in_array($tag, ['p', 'div', 'section', 'article', 'ul', 'ol', 'table', 'tr', 'blockquote'], true) => "\n\n" . trim($inner) . "\n\n",
                in_array($tag, ['td', 'th'], true) => trim($inner) . ' | ',
                default => $inner,
            };
        }
        return $out;
    }
}
