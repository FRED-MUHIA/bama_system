<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Database\Connection;
use Symfony\Component\Console\Input\InputOption;

class PostgresWipeCommand extends Command
{
    use ConfirmableTrait;
    use Prohibitable;

    protected $name = 'db:wipe';

    protected $description = 'Drop all tables, views, and types';

    public function handle(): int
    {
        if ($this->isProhibited() || ! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        $database = $this->option('database');
        $connection = $this->laravel['db']->connection($database);

        if ($connection->getDriverName() !== 'pgsql') {
            return $this->wipeWithSchemaBuilder($connection, $database);
        }

        if ($this->option('drop-views')) {
            $this->dropPostgresViews($connection);
            $this->components->info('Dropped all views successfully.');
        }

        $this->dropPostgresTables($connection);
        $this->components->info('Dropped all tables successfully.');

        if ($this->option('drop-types')) {
            $this->dropPostgresTypes($connection);
            $this->components->info('Dropped all types successfully.');
        }

        $connection->disconnect();

        return Command::SUCCESS;
    }

    protected function wipeWithSchemaBuilder(Connection $connection, ?string $database): int
    {
        if ($this->option('drop-views')) {
            $connection->getSchemaBuilder()->dropAllViews();
            $this->components->info('Dropped all views successfully.');
        }

        $connection->getSchemaBuilder()->dropAllTables();
        $this->components->info('Dropped all tables successfully.');

        if ($this->option('drop-types')) {
            $connection->getSchemaBuilder()->dropAllTypes();
            $this->components->info('Dropped all types successfully.');
        }

        $connection->disconnect();

        return Command::SUCCESS;
    }

    protected function dropPostgresTables(Connection $connection): void
    {
        foreach (array_chunk($this->postgresTables($connection), 20) as $tables) {
            $connection->statement('DROP TABLE IF EXISTS '.implode(', ', $tables).' CASCADE');
        }
    }

    protected function dropPostgresViews(Connection $connection): void
    {
        foreach (array_chunk($this->postgresViews($connection), 20) as $views) {
            $connection->statement('DROP VIEW IF EXISTS '.implode(', ', $views).' CASCADE');
        }

        foreach (array_chunk($this->postgresMaterializedViews($connection), 20) as $views) {
            $connection->statement('DROP MATERIALIZED VIEW IF EXISTS '.implode(', ', $views).' CASCADE');
        }
    }

    protected function dropPostgresTypes(Connection $connection): void
    {
        foreach (array_chunk($this->postgresTypes($connection), 20) as $types) {
            $connection->statement('DROP TYPE IF EXISTS '.implode(', ', $types).' CASCADE');
        }
    }

    protected function postgresTables(Connection $connection): array
    {
        return array_map(
            fn ($row) => $this->qualifiedIdentifier($row->schemaname, $row->tablename),
            $connection->select(<<<'SQL'
                SELECT schemaname, tablename
                FROM pg_tables
                WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
                ORDER BY schemaname, tablename
            SQL)
        );
    }

    protected function postgresViews(Connection $connection): array
    {
        return array_map(
            fn ($row) => $this->qualifiedIdentifier($row->schemaname, $row->viewname),
            $connection->select(<<<'SQL'
                SELECT schemaname, viewname
                FROM pg_views
                WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
                ORDER BY schemaname, viewname
            SQL)
        );
    }

    protected function postgresMaterializedViews(Connection $connection): array
    {
        return array_map(
            fn ($row) => $this->qualifiedIdentifier($row->schemaname, $row->matviewname),
            $connection->select(<<<'SQL'
                SELECT schemaname, matviewname
                FROM pg_matviews
                WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
                ORDER BY schemaname, matviewname
            SQL)
        );
    }

    protected function postgresTypes(Connection $connection): array
    {
        return array_map(
            fn ($row) => $this->qualifiedIdentifier($row->schemaname, $row->typname),
            $connection->select(<<<'SQL'
                SELECT n.nspname AS schemaname, t.typname
                FROM pg_type t
                JOIN pg_namespace n ON n.oid = t.typnamespace
                LEFT JOIN pg_class c ON c.oid = t.typrelid
                WHERE n.nspname NOT IN ('pg_catalog', 'information_schema')
                  AND t.typtype IN ('c', 'd', 'e')
                  AND c.oid IS NULL
                ORDER BY n.nspname, t.typname
            SQL)
        );
    }

    protected function qualifiedIdentifier(string $schema, string $name): string
    {
        return $this->quoteIdentifier($schema).'.'.$this->quoteIdentifier($name);
    }

    protected function quoteIdentifier(string $value): string
    {
        return '"'.str_replace('"', '""', $value).'"';
    }

    protected function getOptions(): array
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['drop-views', null, InputOption::VALUE_NONE, 'Drop all tables and views'],
            ['drop-types', null, InputOption::VALUE_NONE, 'Drop all tables and types (Postgres only)'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
