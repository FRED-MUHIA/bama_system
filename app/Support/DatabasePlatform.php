<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabasePlatform
{
    public static function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    public static function isPostgres(): bool
    {
        return self::driver() === 'pgsql';
    }

    public static function dateBucketExpression(string $column, string $bucket): string
    {
        $wrapped = self::wrap($column);

        return match ($bucket) {
            'hour' => match (self::driver()) {
                'pgsql' => "EXTRACT(HOUR FROM {$wrapped})::integer",
                'sqlite' => "CAST(strftime('%H', {$wrapped}) AS INTEGER)",
                default => "HOUR({$wrapped})",
            },
            'month' => match (self::driver()) {
                'pgsql' => "EXTRACT(MONTH FROM {$wrapped})::integer",
                'sqlite' => "CAST(strftime('%m', {$wrapped}) AS INTEGER)",
                default => "MONTH({$wrapped})",
            },
            default => match (self::driver()) {
                'sqlite' => "date({$wrapped})",
                default => "DATE({$wrapped})",
            },
        };
    }

    public static function deleteDuplicates(string $table, array $columns, string $orderColumn = 'id'): void
    {
        if (! Schema::hasColumn($table, $orderColumn)) {
            return;
        }

        $wrappedTable = DB::getQueryGrammar()->wrapTable($table);
        $wrappedOrderColumn = self::wrap($orderColumn);
        $partitionColumns = implode(', ', array_map(self::wrap(...), $columns));

        DB::statement(
            "DELETE FROM {$wrappedTable} WHERE {$wrappedOrderColumn} IN (".
            "SELECT {$wrappedOrderColumn} FROM (".
            "SELECT {$wrappedOrderColumn}, ROW_NUMBER() OVER (PARTITION BY {$partitionColumns} ORDER BY {$wrappedOrderColumn}) AS duplicate_rank ".
            "FROM {$wrappedTable}".
            ') AS ranked_duplicates WHERE duplicate_rank > 1'.
            ')'
        );
    }

    public static function hasIndex(string $table, string|array $index, ?string $type = null): bool
    {
        return Schema::hasIndex($table, $index, $type);
    }

    public static function alterNumericColumn(
        string $table,
        string $column,
        string $type,
        string $default = '0',
        bool $nullable = false,
        ?string $using = null
    ): void {
        $wrappedTable = DB::getQueryGrammar()->wrapTable($table);
        $wrappedColumn = self::wrap($column);
        $driver = self::driver();

        if ($driver === 'pgsql') {
            $using ??= "{$wrappedColumn}::{$type}";
            $nullability = $nullable ? 'DROP NOT NULL' : 'SET NOT NULL';

            DB::statement(
                "ALTER TABLE {$wrappedTable} ".
                "ALTER COLUMN {$wrappedColumn} TYPE {$type} USING {$using}, ".
                "ALTER COLUMN {$wrappedColumn} SET DEFAULT {$default}, ".
                "ALTER COLUMN {$wrappedColumn} {$nullability}"
            );

            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $nullability = $nullable ? 'NULL' : 'NOT NULL';
            DB::statement("ALTER TABLE {$wrappedTable} MODIFY {$wrappedColumn} {$type} {$nullability} DEFAULT {$default}");
        }
    }

    private static function wrap(string $value): string
    {
        return DB::getQueryGrammar()->wrap($value);
    }
}
