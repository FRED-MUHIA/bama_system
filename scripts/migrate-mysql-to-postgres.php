<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$source = DB::connection('legacy_mysql');
$target = DB::connection('pgsql');

$sourceDatabase = (string) config('database.connections.legacy_mysql.database');

$sourceTables = collect($source->select(
    'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ? ORDER BY TABLE_NAME',
    [$sourceDatabase, 'BASE TABLE']
))->pluck('TABLE_NAME')->values();

$targetTables = collect(Schema::connection('pgsql')->getTableListing())
    ->map(fn (string $table) => str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table)
    ->values();
$tables = $sourceTables->intersect($targetTables)->values();
$missingInTarget = $sourceTables->diff($targetTables)->values();
$missingInSource = $targetTables->diff($sourceTables)->values();

printf("Source tables: %d\n", $sourceTables->count());
printf("Target tables: %d\n", $targetTables->count());
printf("Copying tables: %d\n", $tables->count());

if ($missingInTarget->isNotEmpty()) {
    printf("Skipping %d source table(s) not present in PostgreSQL: %s\n", $missingInTarget->count(), $missingInTarget->implode(', '));
}

if ($missingInSource->isNotEmpty()) {
    printf("Leaving %d PostgreSQL-only table(s) untouched: %s\n", $missingInSource->count(), $missingInSource->implode(', '));
}

$target->statement('SET session_replication_role = replica');

try {
    truncateTargetTables($target, $tables->all());

    foreach ($tables as $table) {
        copyTable($source, $target, $sourceDatabase, $table);
    }
} finally {
    $target->statement('SET session_replication_role = DEFAULT');
}

foreach ($tables as $table) {
    resetSequences($target, $table);
}

$target->statement('ANALYZE');

echo "Migration complete.\n";

function truncateTargetTables(ConnectionInterface $target, array $tables): void
{
    if ($tables === []) {
        return;
    }

    $quotedTables = implode(', ', array_map(fn (string $table) => quoteIdentifier($table), $tables));
    $target->statement("TRUNCATE TABLE {$quotedTables} RESTART IDENTITY CASCADE");
}

function copyTable(ConnectionInterface $source, ConnectionInterface $target, string $sourceDatabase, string $table): void
{
    $sourceColumns = collect($source->select(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
        [$sourceDatabase, $table]
    ))->pluck('COLUMN_NAME')->all();

    $targetColumns = collect(Schema::connection('pgsql')->getColumns($table));
    $targetColumnNames = $targetColumns->pluck('name')->all();
    $copyColumns = array_values(array_intersect($sourceColumns, $targetColumnNames));
    $typeMap = $targetColumns->mapWithKeys(fn (array $column) => [$column['name'] => strtolower($column['type_name'] ?? $column['type'] ?? '')])->all();

    if ($copyColumns === []) {
        printf("%s: no shared columns, skipped\n", $table);

        return;
    }

    $count = (int) $source->table($table)->count();
    if ($count === 0) {
        printf("%s: 0 rows\n", $table);

        return;
    }

    $copied = 0;
    $orderColumn = in_array('id', $sourceColumns, true) ? 'id' : $sourceColumns[0];

    $source->table($table)
        ->select($copyColumns)
        ->orderBy($orderColumn)
        ->chunk(500, function ($rows) use ($target, $table, $copyColumns, $typeMap, &$copied) {
            $payload = [];

            foreach ($rows as $row) {
                $data = [];
                foreach ($copyColumns as $column) {
                    $data[$column] = normalizeValue($row->{$column}, $typeMap[$column] ?? '');
                }
                $payload[] = $data;
            }

            if ($payload !== []) {
                $target->table($table)->insert($payload);
                $copied += count($payload);
            }
        });

    printf("%s: %d rows\n", $table, $copied);
}

function normalizeValue(mixed $value, string $type): mixed
{
    if ($value === null) {
        return null;
    }

    if (str_starts_with((string) $value, '0000-00-00')) {
        return null;
    }

    if ($type === 'bool' || $type === 'boolean') {
        return (bool) $value;
    }

    if (($type === 'json' || $type === 'jsonb') && is_string($value) && trim($value) === '') {
        return null;
    }

    return $value;
}

function resetSequences(ConnectionInterface $target, string $table): void
{
    foreach (Schema::connection('pgsql')->getColumnListing($table) as $column) {
        $sequence = $target->selectOne('SELECT pg_get_serial_sequence(?, ?) AS sequence', [$table, $column])->sequence ?? null;

        if (! $sequence) {
            continue;
        }

        $max = $target->table($table)->max($column);
        $target->statement('SELECT setval(?, ?, ?)', [$sequence, (int) ($max ?? 1), $max !== null]);
    }
}

function quoteIdentifier(string $identifier): string
{
    return '"'.str_replace('"', '""', $identifier).'"';
}
