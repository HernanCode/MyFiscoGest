<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Copia las tablas de dominio desde un fichero SQLite a la conexión de base de
 * datos por defecto (MySQL), preservando los IDs originales para que las claves
 * foráneas sigan siendo válidas.
 *
 * Pensado para subir los datos de desarrollo a la instancia de producción sin
 * exponer el MySQL: se ejecuta EN el servidor de producción, leyendo un fichero
 * SQLite que se sube aparte.
 *
 * No toca tablas de infraestructura (migrations, sessions, cache, jobs).
 */
class ImportSqliteToMysql extends Command
{
    protected $signature = 'db:import-sqlite
                            {--source= : Ruta al fichero SQLite de origen (por defecto database/database.sqlite)}
                            {--upsert : Actualiza las filas existentes por ID en vez de vaciar las tablas}
                            {--chunk=500 : Número de filas por lote de inserción}
                            {--force : No pedir confirmación}';

    protected $description = 'Copia las tablas de dominio desde un fichero SQLite a la conexión por defecto (MySQL), preservando los IDs.';

    /**
     * Tablas en orden de dependencias: los padres antes que los hijos.
     *
     * @var list<string>
     */
    private const TABLES = [
        'users',
        'categories',
        'fiscal_years',
        'recurring_movements',
        'movements',
        'quarter_filings',
        'invitations',
    ];

    public function handle(): int
    {
        $source = $this->option('source') ?: database_path('database.sqlite');

        if (! is_file($source)) {
            $this->error("No se encuentra el fichero SQLite de origen: {$source}");

            return self::FAILURE;
        }

        /** @var Connection $target */
        $target = DB::connection();

        if ($target->getDriverName() === 'sqlite') {
            $this->error('La conexión por defecto es SQLite. Configura DB_CONNECTION=mysql antes de ejecutar este comando.');

            return self::FAILURE;
        }

        // Conexión de solo lectura al SQLite de origen, definida en tiempo de ejecución.
        config(['database.connections.sqlite_source' => [
            'driver' => 'sqlite',
            'database' => $source,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        $src = DB::connection('sqlite_source');

        $upsert = (bool) $this->option('upsert');
        $chunk = max(1, (int) $this->option('chunk'));

        $this->info("Origen : SQLite ({$source})");
        $this->info('Destino: '.strtoupper($target->getDriverName())." ({$target->getDatabaseName()})");
        $this->newLine();

        if (! $this->option('force')) {
            $action = $upsert
                ? 'insertar o actualizar (upsert)'
                : 'VACIAR y reemplazar por';

            if (! $this->confirm("Se van a {$action} las tablas de dominio. ¿Continuar?", false)) {
                $this->warn('Cancelado.');

                return self::SUCCESS;
            }
        }

        // MySQL no permite truncar/insertar respetando FK sin desactivarlas.
        $target->statement('SET FOREIGN_KEY_CHECKS=0');

        $summary = [];
        try {
            foreach (self::TABLES as $table) {
                $summary[$table] = $this->copyTable($src, $target, $table, $upsert, $chunk);
            }
        } finally {
            $target->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->newLine();
        $this->table(
            ['Tabla', 'Filas copiadas'],
            collect($summary)->map(fn (int $rows, string $table) => [$table, $rows])->values()->all(),
        );
        $this->newLine();
        $this->info('✔ Importación completada.');

        return self::SUCCESS;
    }

    private function copyTable(Connection $src, Connection $target, string $table, bool $upsert, int $chunk): int
    {
        if (! $target->getSchemaBuilder()->hasTable($table)) {
            $this->warn("  ! La tabla '{$table}' no existe en destino; se omite.");

            return 0;
        }

        $rows = $src->table($table)->get();
        $count = $rows->count();

        if (! $upsert) {
            $target->table($table)->truncate();
        }

        if ($count === 0) {
            return 0;
        }

        $updateColumns = null;

        $rows->chunk($chunk)->each(function ($batch) use ($target, $table, $upsert, &$updateColumns): void {
            $data = $batch->map(fn ($row) => $this->castRow((array) $row))->all();

            if ($upsert) {
                $updateColumns ??= array_values(array_diff(array_keys($data[0]), ['id']));
                $target->table($table)->upsert($data, ['id'], $updateColumns);
            } else {
                $target->table($table)->insert($data);
            }
        });

        return $count;
    }

    /**
     * Normaliza valores del driver SQLite (booleans) para que encajen en columnas
     * tinyint/decimal/datetime de MySQL. Nulls, strings y números se dejan igual.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function castRow(array $row): array
    {
        return array_map(static function ($value) {
            if (is_bool($value)) {
                return (int) $value;
            }

            return $value;
        }, $row);
    }
}
