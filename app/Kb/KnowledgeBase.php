<?php
declare(strict_types=1);

namespace App\Kb;

use PDOException;
use RuntimeException;

/**
 * The retrieval half of BRIX AI: indexes documents into passages and finds
 * the passages that best match a question, using MySQL full-text search.
 */
final class KnowledgeBase
{
    public const DOC_TYPES = [
        'case_study' => 'Case study',
        'product'    => 'Product info',
        'guide'      => 'Guide / article',
        'faq'        => 'FAQ',
        'other'      => 'Other',
    ];

    /** Re-split a document's text into passages. Returns the passage count. */
    public static function index(int $documentId): int
    {
        $doc = db_one('SELECT id, content FROM kb_documents WHERE id = ?', [$documentId]);
        if (!$doc) {
            throw new RuntimeException('Document not found.');
        }

        $chunks = (new Chunker())->chunk((string) $doc['content']);
        $pdo = db();
        $pdo->beginTransaction();
        try {
            db_exec('DELETE FROM kb_chunks WHERE document_id = ?', [$documentId]);
            $stmt = $pdo->prepare('INSERT INTO kb_chunks (document_id, chunk_index, heading, content, char_count) VALUES (?, ?, ?, ?, ?)');
            foreach ($chunks as $i => $chunk) {
                $stmt->execute([$documentId, $i, $chunk['heading'], $chunk['content'], mb_strlen($chunk['content'])]);
            }
            db_update('kb_documents', [
                'chunk_count' => count($chunks),
                'char_count'  => mb_strlen((string) $doc['content']),
                'status'      => count($chunks) > 0 ? 'ready' : 'error',
                'error'       => count($chunks) > 0 ? null : 'The document has no text to index.',
                'updated_at'  => now(),
            ], 'id = ?', [$documentId]);
            $pdo->commit();
        } catch (\Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
        return count($chunks);
    }

    /** Add the synonyms an admin configured, so "AOV" also finds "average order value". */
    public static function expandQuery(string $query): string
    {
        $expanded = $query;
        $lower = mb_strtolower($query);
        foreach (lines((string) setting('kb_synonyms')) as $line) {
            if (!str_contains($line, '=>')) {
                continue;
            }
            [$term, $extra] = array_map('trim', explode('=>', $line, 2));
            if ($term !== '' && preg_match('/(?<![\p{L}\p{N}])' . preg_quote(mb_strtolower($term), '/') . '/u', $lower)) {
                $expanded .= ' ' . $extra;
            }
        }
        return $expanded;
    }

    /**
     * Passages that best match the query, at most two per document.
     *
     * @return list<array{chunk_id:int, document_id:int, heading:?string, content:string, score:float, title:string, doc_type:string, brand:?string, industry:?string, result_value:?string, result_label:?string, public_url:?string, product_id:?int}>
     */
    public static function search(string $query, int $limit = 6): array
    {
        $query = trim(self::expandQuery($query));
        // Boolean operators would change the meaning of a natural-language search.
        $query = trim((string) preg_replace('/[+\-<>()~*"@]+/', ' ', $query));
        if ($query === '') {
            return [];
        }

        try {
            $rows = db_all(
                "SELECT c.id AS chunk_id, c.document_id, c.heading, c.content,
                        MATCH(c.heading, c.content) AGAINST (? IN NATURAL LANGUAGE MODE) AS chunk_score,
                        MATCH(d.title, d.summary, d.brand, d.industry) AGAINST (? IN NATURAL LANGUAGE MODE) AS doc_score,
                        d.title, d.doc_type, d.brand, d.industry, d.result_value, d.result_label, d.public_url, d.product_id
                   FROM kb_chunks c
                   JOIN kb_documents d ON d.id = c.document_id
                  WHERE d.is_active = 1 AND d.status = 'ready'
                    AND (MATCH(c.heading, c.content) AGAINST (? IN NATURAL LANGUAGE MODE) > 0
                         OR MATCH(d.title, d.summary, d.brand, d.industry) AGAINST (? IN NATURAL LANGUAGE MODE) > 0)
                  ORDER BY (MATCH(c.heading, c.content) AGAINST (? IN NATURAL LANGUAGE MODE)
                          + 0.5 * MATCH(d.title, d.summary, d.brand, d.industry) AGAINST (? IN NATURAL LANGUAGE MODE)) DESC
                  LIMIT 40",
                array_fill(0, 6, $query)
            );
        } catch (PDOException $ex) {
            log_message('ai', 'Knowledge base search failed: ' . $ex->getMessage());
            return [];
        }

        $results = [];
        $perDoc = [];
        $top = null;
        foreach ($rows as $row) {
            $score = (float) $row['chunk_score'] + 0.5 * (float) $row['doc_score'];
            $top ??= $score;
            // Drop the long tail of weak matches.
            if ($top > 0 && $score < $top * 0.2) {
                break;
            }
            $docId = (int) $row['document_id'];
            if (($perDoc[$docId] ?? 0) >= 2) {
                continue;
            }
            $perDoc[$docId] = ($perDoc[$docId] ?? 0) + 1;
            $results[] = [
                'chunk_id'     => (int) $row['chunk_id'],
                'document_id'  => $docId,
                'heading'      => $row['heading'],
                'content'      => $row['content'],
                'score'        => round($score, 3),
                'title'        => $row['title'],
                'doc_type'     => $row['doc_type'],
                'brand'        => $row['brand'],
                'industry'     => $row['industry'],
                'result_value' => $row['result_value'],
                'result_label' => $row['result_label'],
                'public_url'   => $row['public_url'],
                'product_id'   => $row['product_id'] !== null ? (int) $row['product_id'] : null,
            ];
            if (count($results) >= $limit) {
                break;
            }
        }
        return $results;
    }

    /** Case studies listed in the system prompt, so the model knows the whole library. */
    public static function caseStudyIndex(): array
    {
        return db_all(
            "SELECT d.id, d.title, d.brand, d.industry, d.result_value, d.result_label, d.summary, p.name AS product_name
               FROM kb_documents d LEFT JOIN products p ON p.id = d.product_id
              WHERE d.is_active = 1 AND d.status = 'ready' AND d.doc_type = 'case_study'
              ORDER BY d.sort_order, d.id"
        );
    }

    /** Store an uploaded or pasted document and index it. Returns the new id. */
    public static function create(array $meta, string $text, ?array $file = null): int
    {
        $text = TextExtractor::normalize($text);
        $row = $meta + [
            'source'      => $file ? 'upload' : 'text',
            'content'     => $text,
            'char_count'  => mb_strlen($text),
            'status'      => 'processing',
            'uploaded_by' => current_admin()['id'] ?? null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ];
        if ($file) {
            $row += [
                'original_name' => mb_substr($file['original'], 0, 255),
                'stored_name'   => $file['name'],
                'mime'          => $file['mime'],
                'file_size'     => $file['size'],
            ];
        }
        $id = db_insert('kb_documents', $row);
        self::index($id);
        return $id;
    }

    public static function storageDir(): string
    {
        return BASE_PATH . '/storage/kb';
    }

    public static function delete(int $id): void
    {
        $doc = db_one('SELECT stored_name FROM kb_documents WHERE id = ?', [$id]);
        if ($doc && $doc['stored_name']) {
            $file = self::storageDir() . '/' . basename($doc['stored_name']);
            if (is_file($file)) {
                @unlink($file);
            }
        }
        db_exec('DELETE FROM kb_documents WHERE id = ?', [$id]);
    }
}
