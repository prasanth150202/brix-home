<?php
declare(strict_types=1);

use App\Kb\KnowledgeBase;
use App\Kb\TextExtractor;

function admin_kb_index(): void
{
    require_admin();
    $type = (string) ($_GET['type'] ?? '');
    $search = trim((string) ($_GET['q'] ?? ''));

    $where = ['1 = 1'];
    $params = [];
    if (isset(KnowledgeBase::DOC_TYPES[$type])) {
        $where[] = 'd.doc_type = ?';
        $params[] = $type;
    }
    if ($search !== '') {
        $where[] = '(d.title LIKE ? OR d.brand LIKE ? OR d.original_name LIKE ?)';
        array_push($params, "%$search%", "%$search%", "%$search%");
    }
    $documents = db_all(
        'SELECT d.id, d.title, d.doc_type, d.source, d.original_name, d.file_size, d.brand, d.result_value, d.result_label,
                d.chunk_count, d.char_count, d.status, d.error, d.is_active, d.is_featured, d.updated_at, p.name AS product_name
           FROM kb_documents d LEFT JOIN products p ON p.id = d.product_id
          WHERE ' . implode(' AND ', $where) . '
          ORDER BY d.doc_type = \'case_study\' DESC, d.sort_order, d.id DESC',
        $params
    );
    $counts = db_all('SELECT doc_type, COUNT(*) AS n FROM kb_documents GROUP BY doc_type');

    render('admin/kb/index', [
        'title'     => 'Knowledge base',
        'documents' => $documents,
        'counts'    => array_column($counts, 'n', 'doc_type'),
        'type'      => $type,
        'search'    => $search,
    ], 'admin/layout');
}

/** Metadata fields shared by the create and edit forms. */
function kb_meta_from_request(): array
{
    $type = (string) input('doc_type', 'case_study');
    $productId = input_int('product_id');
    $publicUrl = trim((string) input('public_url', ''));
    return [
        'title'        => mb_substr((string) input('title', ''), 0, 255),
        'doc_type'     => isset(KnowledgeBase::DOC_TYPES[$type]) ? $type : 'other',
        'brand'        => mb_substr((string) input('brand', ''), 0, 160) ?: null,
        'industry'     => mb_substr((string) input('industry', ''), 0, 120) ?: null,
        'result_value' => mb_substr((string) input('result_value', ''), 0, 40) ?: null,
        'result_label' => mb_substr((string) input('result_label', ''), 0, 160) ?: null,
        'summary'      => trim((string) input('summary', '')) ?: null,
        'product_id'   => $productId && db_val('SELECT id FROM products WHERE id = ?', [$productId]) ? $productId : null,
        'public_url'   => $publicUrl !== '' ? mb_substr($publicUrl, 0, 500) : null,
        'is_active'    => input_bool('is_active'),
        'is_featured'  => input_bool('is_featured'),
        'sort_order'   => input_int('sort_order'),
    ];
}

function admin_kb_create(): void
{
    require_admin();
    $errors = [];

    if (is_post()) {
        verify_csrf();
        $meta = kb_meta_from_request();
        $mode = input('mode') === 'text' ? 'text' : 'file';
        $file = null;
        $text = '';

        try {
            if ($mode === 'file') {
                $file = store_upload($_FILES['document'] ?? [], TextExtractor::EXTENSIONS, KnowledgeBase::storageDir(), DOCUMENT_MAX_BYTES);
                try {
                    $text = TextExtractor::fromFile($file['path'], $file['ext']);
                } catch (Throwable $ex) {
                    @unlink($file['path']);
                    throw $ex;
                }
                if ($meta['title'] === '') {
                    $meta['title'] = kb_title_guess($text, $file['original']);
                }
            } else {
                $text = (string) ($_POST['content'] ?? '');
                if (trim($text) === '') {
                    throw new RuntimeException('Paste the document text, or switch to file upload.');
                }
                if ($meta['title'] === '') {
                    $meta['title'] = kb_title_guess($text, 'Untitled document');
                }
            }
            $id = KnowledgeBase::create($meta, $text, $file);
            $chunks = (int) db_val('SELECT chunk_count FROM kb_documents WHERE id = ?', [$id]);
            forget_input();
            flash('success', "\"{$meta['title']}\" added and split into $chunks passages. BRIX AI can use it now.");
            redirect('/admin/kb/' . $id);
        } catch (Throwable $ex) {
            $errors['document'] = $ex->getMessage();
            remember_input();
        }
    }

    render('admin/kb/form', [
        'title'    => 'Add document',
        'doc'      => null,
        'errors'   => $errors,
        'products' => db_all('SELECT id, name FROM products ORDER BY sort_order, name'),
    ], 'admin/layout');
    forget_input();
}

function kb_title_guess(string $text, string $fallback): string
{
    if (preg_match('/^#{1,2}\s+(.+)$/m', $text, $m)) {
        return mb_substr(trim($m[1], " #*\""), 0, 255);
    }
    $name = pathinfo($fallback, PATHINFO_FILENAME);
    return mb_substr(ucfirst(trim((string) preg_replace('/[_\-]+/', ' ', $name))), 0, 255);
}

function admin_kb_edit(string $id): void
{
    require_admin();
    $doc = db_one('SELECT * FROM kb_documents WHERE id = ?', [(int) $id]) ?? abort(404);
    $errors = [];

    if (is_post()) {
        verify_csrf();
        $meta = kb_meta_from_request();
        if ($meta['title'] === '') {
            $errors['title'] = 'Give the document a title.';
        }
        $newText = $_POST['content'] ?? null;
        $file = null;

        try {
            if (!empty($_FILES['document']['name'])) {
                $file = store_upload($_FILES['document'], TextExtractor::EXTENSIONS, KnowledgeBase::storageDir(), DOCUMENT_MAX_BYTES);
                $newText = TextExtractor::fromFile($file['path'], $file['ext']);
            }
        } catch (Throwable $ex) {
            $errors['document'] = $ex->getMessage();
        }

        if (!$errors) {
            $update = $meta + ['updated_at' => now()];
            $reindex = false;
            if (is_string($newText) && TextExtractor::normalize($newText) !== (string) $doc['content']) {
                $update['content'] = TextExtractor::normalize($newText);
                $reindex = true;
            }
            if ($file) {
                if ($doc['stored_name']) {
                    @unlink(KnowledgeBase::storageDir() . '/' . basename($doc['stored_name']));
                }
                $update += ['source' => 'upload', 'original_name' => mb_substr($file['original'], 0, 255), 'stored_name' => $file['name'], 'mime' => $file['mime'], 'file_size' => $file['size']];
            }
            db_update('kb_documents', $update, 'id = ?', [$doc['id']]);
            $message = 'Document saved.';
            if ($reindex) {
                $chunks = KnowledgeBase::index((int) $doc['id']);
                $message .= " Text re-indexed into $chunks passages.";
            }
            flash('success', $message);
            redirect('/admin/kb/' . $doc['id']);
        }
        remember_input();
    }

    render('admin/kb/form', [
        'title'    => $doc['title'],
        'doc'      => $doc,
        'errors'   => $errors,
        'chunks'   => db_all('SELECT chunk_index, heading, content, char_count FROM kb_chunks WHERE document_id = ? ORDER BY chunk_index', [$doc['id']]),
        'products' => db_all('SELECT id, name FROM products ORDER BY sort_order, name'),
    ], 'admin/layout');
    forget_input();
}

function admin_kb_reindex(string $id): void
{
    require_admin();
    verify_csrf();
    $chunks = KnowledgeBase::index((int) $id);
    flash('success', "Re-indexed into $chunks passages.");
    redirect('/admin/kb/' . (int) $id);
}

function admin_kb_delete(string $id): void
{
    require_admin();
    verify_csrf();
    KnowledgeBase::delete((int) $id);
    flash('success', 'Document deleted. BRIX AI will no longer use it.');
    redirect('/admin/kb');
}

function admin_kb_download(string $id): void
{
    require_admin();
    $doc = db_one('SELECT title, original_name, stored_name, mime, content FROM kb_documents WHERE id = ?', [(int) $id]) ?? abort(404);
    $file = $doc['stored_name'] ? KnowledgeBase::storageDir() . '/' . basename($doc['stored_name']) : null;

    if ($file && is_file($file)) {
        header('Content-Type: ' . ($doc['mime'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', (string) $doc['original_name']) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return;
    }
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . slugify($doc['title']) . '.txt"');
    echo $doc['content'];
}

function admin_kb_test(): void
{
    require_admin();
    $question = trim((string) input('q', ''));
    $results = $question !== '' ? KnowledgeBase::search($question, max(1, (int) setting('ai_context_chunks', 6))) : [];

    render('admin/kb/test', [
        'title'    => 'Test retrieval',
        'question' => $question,
        'expanded' => $question !== '' ? KnowledgeBase::expandQuery($question) : '',
        'results'  => $results,
    ], 'admin/layout');
}
