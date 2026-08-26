<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SchemaCache
{
    private static array $tables = [];
    private static array $columns = [];

    public static function hasTable(string $table): bool
    {
        return self::$tables[$table] ??= self::probe(fn () => Schema::hasTable($table));
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = $table.'.'.$column;

        return self::$columns[$key] ??= self::hasTable($table) && self::probe(fn () => Schema::hasColumn($table, $column));
    }

    public static function flush(): void
    {
        self::$tables = [];
        self::$columns = [];
    }

    private static function probe(callable $callback): bool
    {
        try {
            return (bool) $callback();
        } catch (QueryException) {
            return false;
        } catch (Throwable) {
            return false;
        }
    }
}
