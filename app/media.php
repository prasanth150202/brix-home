<?php
declare(strict_types=1);

const IMAGE_MAX_BYTES = 8 * 1024 * 1024;
const DOCUMENT_MAX_BYTES = 30 * 1024 * 1024;

/** File types we accept, with the MIME types finfo may report for each. */
function allowed_file_types(): array
{
    return [
        'jpg'      => ['image/jpeg'],
        'jpeg'     => ['image/jpeg'],
        'png'      => ['image/png'],
        'gif'      => ['image/gif'],
        'webp'     => ['image/webp'],
        'pdf'      => ['application/pdf'],
        'docx'     => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'txt'      => ['text/'],
        'md'       => ['text/', 'application/octet-stream'],
        'markdown' => ['text/', 'application/octet-stream'],
        'html'     => ['text/html', 'text/plain'],
        'htm'      => ['text/html', 'text/plain'],
    ];
}

function upload_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is larger than the server allows (' . ini_get('upload_max_filesize') . ').',
        UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Choose a file to upload.',
        default => 'The server could not save the upload (error ' . $code . ').',
    };
}

/**
 * Check an uploaded file and move it into $dir under a random name.
 *
 * @return array{path:string, name:string, original:string, mime:string, size:int, ext:string}
 */
function store_upload(array $file, array $extensions, string $dir, int $maxBytes): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($error));
    }
    if (!is_uploaded_file($file['tmp_name']) && PHP_SAPI !== 'cli') {
        throw new RuntimeException('That upload could not be verified.');
    }
    $size = (int) $file['size'];
    if ($size <= 0) {
        throw new RuntimeException('The file is empty.');
    }
    if ($size > $maxBytes) {
        throw new RuntimeException('The file is ' . human_filesize($size) . '. The limit is ' . human_filesize($maxBytes) . '.');
    }

    $original = basename((string) $file['name']);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, $extensions, true)) {
        throw new RuntimeException('Files of type .' . ($ext ?: '?') . ' are not allowed. Use ' . implode(', ', array_map(static fn ($x) => ".$x", $extensions)) . '.');
    }

    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $okMime = false;
    foreach (allowed_file_types()[$ext] ?? [] as $allowed) {
        if (str_ends_with($allowed, '/') ? str_starts_with($mime, $allowed) : $mime === $allowed) {
            $okMime = true;
            break;
        }
    }
    if (!$okMime) {
        throw new RuntimeException("The file's contents ($mime) don't match its .$ext extension.");
    }

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create the upload folder.');
    }
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $target = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name;
    $moved = PHP_SAPI === 'cli' ? copy($file['tmp_name'], $target) : move_uploaded_file($file['tmp_name'], $target);
    if (!$moved) {
        throw new RuntimeException('Could not save the file on the server.');
    }

    return ['path' => $target, 'name' => $name, 'original' => $original, 'mime' => $mime, 'size' => $size, 'ext' => $ext];
}

/** Save an uploaded image into public/uploads and the media library. */
function upload_image(array $file, string $alt = ''): array
{
    $sub = date('Y/m');
    $stored = store_upload($file, ['jpg', 'jpeg', 'png', 'gif', 'webp'], BASE_PATH . '/public/uploads/' . $sub, IMAGE_MAX_BYTES);
    $dims = @getimagesize($stored['path']) ?: [null, null];

    $row = [
        'path'          => 'uploads/' . $sub . '/' . $stored['name'],
        'original_name' => mb_substr($stored['original'], 0, 255),
        'mime'          => $stored['mime'],
        'size'          => $stored['size'],
        'width'         => $dims[0],
        'height'        => $dims[1],
        'alt'           => $alt !== '' ? mb_substr($alt, 0, 255) : null,
        'uploaded_by'   => current_admin()['id'] ?? null,
        'created_at'    => now(),
    ];
    $row['id'] = db_insert('media', $row);
    return $row;
}

function delete_media(int $id): void
{
    $media = db_one('SELECT * FROM media WHERE id = ?', [$id]);
    if (!$media) {
        return;
    }
    $file = BASE_PATH . '/public/' . $media['path'];
    if (str_starts_with($media['path'], 'uploads/') && is_file($file)) {
        @unlink($file);
    }
    db_exec('DELETE FROM media WHERE id = ?', [$id]);
}
