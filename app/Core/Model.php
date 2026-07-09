<?php

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];

    protected static function db(): PDO
    {
        return Database::connection();
    }

    public static function find(int|string $id): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function all(string $orderBy = ''): array
    {
        $sql = 'SELECT * FROM ' . static::$table;
        if ($orderBy !== '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return static::db()->query($sql)->fetchAll();
    }

    public static function where(string $column, mixed $value): array
    {
        $stmt = static::db()->prepare('SELECT * FROM ' . static::$table . ' WHERE ' . $column . ' = ?');
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $data = array_intersect_key($data, array_flip(static::$fillable));
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::$table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = static::db()->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)static::db()->lastInsertId();
    }

    public static function update(int|string $id, array $data): bool
    {
        $data = array_intersect_key($data, array_flip(static::$fillable));
        $assignments = implode(', ', array_map(fn (string $column) => $column . ' = ?', array_keys($data)));

        $sql = sprintf('UPDATE %s SET %s WHERE %s = ?', static::$table, $assignments, static::$primaryKey);

        $stmt = static::db()->prepare($sql);
        return $stmt->execute([...array_values($data), $id]);
    }

    public static function delete(int|string $id): bool
    {
        $stmt = static::db()->prepare('DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?');
        return $stmt->execute([$id]);
    }
}
