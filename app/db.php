<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Keep MySQL's NOW()/CURRENT_TIMESTAMP on the same clock as PHP's date().
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }
    return $pdo;
}

function db_query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_val(string $sql, array $params = []): mixed
{
    $value = db_query($sql, $params)->fetchColumn();
    return $value === false ? null : $value;
}

function db_exec(string $sql, array $params = []): int
{
    return db_query($sql, $params)->rowCount();
}

/** Insert a row. Column names come from code, never from the request. */
function db_insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = sprintf(
        'INSERT INTO `%s` (%s) VALUES (%s)',
        $table,
        implode(', ', array_map(static fn ($c) => "`$c`", $cols)),
        implode(', ', array_fill(0, count($cols), '?'))
    );
    db_query($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function db_update(string $table, array $data, string $where, array $params = []): int
{
    $set = implode(', ', array_map(static fn ($c) => "`$c` = ?", array_keys($data)));
    return db_exec("UPDATE `$table` SET $set WHERE $where", [...array_values($data), ...$params]);
}

/** A slug that is not used by any other row in the table. */
function unique_slug(string $table, string $slug, ?int $ignoreId = null, string $column = 'slug'): string
{
    $base = $slug !== '' ? $slug : 'item';
    $candidate = $base;
    for ($i = 2; ; $i++) {
        $taken = db_val(
            "SELECT id FROM `$table` WHERE `$column` = ?" . ($ignoreId ? ' AND id <> ?' : '') . ' LIMIT 1',
            $ignoreId ? [$candidate, $ignoreId] : [$candidate]
        );
        if (!$taken) {
            return $candidate;
        }
        $candidate = $base . '-' . $i;
    }
}
