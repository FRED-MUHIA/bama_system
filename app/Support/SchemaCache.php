<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SchemaCache
{
    private const CACHE_TTL_SECONDS = 3600;

    private static array $tables = [];
    private static array $columns = [];

    public static function hasTable(string $table): bool
    {
        if (self::$tables[$table] ?? false) {
            return true;
        }

        return self::$tables[$table] = self::remember(
            'schema-cache:table:'.$table,
            fn () => self::probe(fn () => Schema::hasTable($table))
        );
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = $table.'.'.$column;

        if (self::$columns[$key] ?? false) {
            return true;
        }

        return self::$columns[$key] = self::hasTable($table) && self::remember(
            'schema-cache:column:'.$table.':'.$column,
            fn () => self::probe(fn () => Schema::hasColumn($table, $column))
        );
    }

    public static function flush(): void
    {
        self::$tables = [];
        self::$columns = [];
    }

    private static function remember(string $key, callable $callback): bool
    {
        if (app()->runningUnitTests()) {
            return $callback();
        }

        try {
            return (bool) Cache::remember($key, self::CACHE_TTL_SECONDS, $callback);
        } catch (Throwable) {
            return $callback();
        }
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
