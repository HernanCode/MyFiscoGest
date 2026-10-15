# App fiscal autónomo — V1 (Laravel)

## 1. Modelo de datos

```
fiscal_years (1) ──< movements >── (1) categories
fiscal_years (1) ──< quarter_filings
```

- **fiscal_years**: el año (2026, 2027...).
- **movements**: ingresos y gastos en una sola tabla (`type` = income | expense). Cada uno lleva su factura adjunta.
- **categories**: categorías de gasto/ingreso.
- **quarter_filings**: un registro por trimestre y modelo (303 y 130), con fecha límite y estado.

El trimestre NO tiene tabla propia: se calcula a partir de la fecha del movimiento y se guarda en `quarter` para poder filtrar rápido.

---

## 2. Migraciones

### `create_fiscal_years_table`

```php
Schema::create('fiscal_years', function (Blueprint $table) {
    $table->id();
    $table->unsignedSmallInteger('year')->unique();
    $table->timestamps();
});
```

### `create_categories_table`

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->enum('type', ['income', 'expense']);
    $table->unsignedTinyInteger('default_deductible_pct')->default(100);
    $table->timestamps();
});
```

### `create_movements_table`

```php
Schema::create('movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
    $table->unsignedTinyInteger('quarter');              // 1..4, se calcula desde `date`
    $table->enum('type', ['income', 'expense']);
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

    $table->date('date');                                // fecha de la factura
    $table->string('concept');
    $table->string('counterparty')->nullable();          // cliente o proveedor
    $table->string('invoice_number')->nullable();

    // Importes
    $table->decimal('base_amount', 12, 2);               // base imponible
    $table->decimal('vat_rate', 5, 2)->default(21);      // % IVA
    $table->decimal('vat_amount', 12, 2)->default(0);
    $table->decimal('irpf_rate', 5, 2)->default(0);      // % retención (solo ingresos)
    $table->decimal('irpf_amount', 12, 2)->default(0);
    $table->decimal('total_amount', 12, 2);              // base + IVA - retención

    // Solo gastos
    $table->unsignedTinyInteger('deductible_pct')->default(100);

    // Estado de cobro/pago
    $table->enum('payment_status', ['pending', 'paid'])->default('pending');
    $table->date('paid_at')->nullable();

    // Factura adjunta
    $table->string('invoice_path')->nullable();

    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['fiscal_year_id', 'quarter', 'type']);
});
```

### `create_quarter_filings_table`

```php
Schema::create('quarter_filings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
    $table->unsignedTinyInteger('quarter');
    $table->enum('model', ['303', '130']);
    $table->date('due_date');
    $table->enum('status', ['pending', 'filed'])->default('pending');
    $table->date('filed_at')->nullable();
    $table->decimal('amount_paid', 12, 2)->nullable();
    $table->timestamps();

    $table->unique(['fiscal_year_id', 'quarter', 'model']);
});
```

---

## 3. Modelos

### `Movement`

```php
class Movement extends Model
{
    protected $guarded = [];
    protected $casts = ['date' => 'date', 'paid_at' => 'date'];

    protected static function booted(): void
    {
        static::saving(function (Movement $m) {
            // Trimestre y año fiscal desde la fecha
            $m->quarter = (int) ceil($m->date->month / 3);
            $m->fiscal_year_id = FiscalYear::firstOrCreate(['year' => $m->date->year])->id;

            // Importes calculados
            $m->vat_amount  = round($m->base_amount * $m->vat_rate / 100, 2);
            $m->irpf_amount = round($m->base_amount * $m->irpf_rate / 100, 2);
            $m->total_amount = $m->base_amount + $m->vat_amount - $m->irpf_amount;
        });
    }

    public function fiscalYear() { return $this->belongsTo(FiscalYear::class); }
    public function category()   { return $this->belongsTo(Category::class); }

    public function scopeIncome($q)  { return $q->where('type', 'income'); }
    public function scopeExpense($q) { return $q->where('type', 'expense'); }
}
```

### `FiscalYear`

```php
class FiscalYear extends Model
{
    protected $guarded = [];

    public function movements() { return $this->hasMany(Movement::class); }
    public function filings()   { return $this->hasMany(QuarterFiling::class); }

    protected static function booted(): void
    {
        // Al crear el año, se generan los 8 vencimientos (4 trimestres x 2 modelos)
        static::created(function (FiscalYear $y) {
            $deadlines = [
                1 => "{$y->year}-04-20",
                2 => "{$y->year}-07-20",
                3 => "{$y->year}-10-20",
                4 => ($y->year + 1) . '-01-30',
            ];
            foreach ($deadlines as $q => $date) {
                foreach (['303', '130'] as $model) {
                    $y->filings()->create([
                        'quarter' => $q, 'model' => $model, 'due_date' => $date,
                    ]);
                }
            }
        });
    }
}
```

> Revisa las fechas cada año: si el día 20 cae en festivo o fin de semana, el plazo se mueve al siguiente día hábil.

### `QuarterFiling` y `Category`

```php
class QuarterFiling extends Model
{
    protected $guarded = [];
    protected $casts = ['due_date' => 'date', 'filed_at' => 'date'];

    public function fiscalYear() { return $this->belongsTo(FiscalYear::class); }
}

class Category extends Model
{
    protected $guarded = [];
}
```

---

## 4. Resumen trimestral (servicio)

```php
class QuarterSummary
{
    public static function for(int $yearId, int $quarter): array
    {
        $q = Movement::where('fiscal_year_id', $yearId);

        // ---- Modelo 303 (IVA): solo el trimestre ----
        $vatCharged = (clone $q)->income()->where('quarter', $quarter)->sum('vat_amount');
        $vatPaid    = (clone $q)->expense()->where('quarter', $quarter)
                          ->selectRaw('COALESCE(SUM(vat_amount * deductible_pct / 100), 0) as t')
                          ->value('t');
        $vat303 = $vatCharged - $vatPaid;

        // ---- Modelo 130 (IRPF): acumulado desde enero hasta este trimestre ----
        $incomeBase = (clone $q)->income()->where('quarter', '<=', $quarter)->sum('base_amount');
        $expenseBase = (clone $q)->expense()->where('quarter', '<=', $quarter)
                          ->selectRaw('COALESCE(SUM(base_amount * deductible_pct / 100), 0) as t')
                          ->value('t');
        $netIncome = $incomeBase - $expenseBase;

        $withheld = (clone $q)->income()->where('quarter', '<=', $quarter)->sum('irpf_amount');

        $previous130 = QuarterFiling::where('fiscal_year_id', $yearId)
            ->where('model', '130')->where('quarter', '<', $quarter)
            ->sum('amount_paid');

        $irpf130 = max(0, ($netIncome * 0.20) - $withheld - $previous130);

        return [
            'income_base'   => $incomeBase,
            'expense_base'  => $expenseBase,
            'net_income'    => $netIncome,
            'vat_charged'   => $vatCharged,
            'vat_paid'      => $vatPaid,
            'modelo_303'    => $vat303,   // negativo = a compensar/devolver
            'modelo_130'    => $irpf130,
        ];
    }
}
```

**Simplificaciones de la V1** (a validar con la AEAT o un gestor la primera vez):
- El 130 se calcula con el 20 % sobre el rendimiento neto acumulado, sin reducciones adicionales (por ejemplo, la deducción por rendimientos bajos o el gasto de difícil justificación del 5 % en estimación directa simplificada).
- No contempla prorrata de IVA, recargo de equivalencia ni IVA de operaciones intracomunitarias.
- Los importes son estimaciones para organizarte, no sustituyen a la presentación oficial.

---

## 5. Recordatorios (pendiente)

Retirados por ahora; se volverán a añadir más adelante. La idea cuando toque: avisar
de los vencimientos (303/130) próximos o ya vencidos, con la opción de enviarlo por
correo desde un comando programado en `routes/console.php`.

---

## 6. Pantallas de la V1

1. **Dashboard**: selector de año y trimestre, con tarjetas: ingresos, gastos, resultado, 303 estimado, 130 estimado, próximo vencimiento.
2. **Movimientos**: tabla filtrable por año, trimestre, tipo y estado de cobro.
3. **Nuevo ingreso / gasto**: formulario con subida de factura (PDF o imagen) a `storage/app/private/invoices/{año}/T{n}/`.
4. **Presentaciones**: lista de los 8 vencimientos del año, con botón "Marcar como presentado" (e importe pagado).
5. **Movimientos → pestaña "Recurrentes"**: plantillas de movimientos que se repiten cada mes, con botón "Generar ahora", pausa y edición.

## 7. Orden de construcción sugerido

1. Migraciones y modelos
2. CRUD de movimientos con subida de factura
3. `QuarterSummary` y el dashboard
4. Vencimientos (los recordatorios por correo quedan pendientes)
5. Exportación CSV (implementada): movimientos filtrados para el gestor y copia
   completa en ZIP (CSV + facturas). Pendiente: importación (fase 2).

## 8. Movimientos recurrentes

Plantillas de movimientos que se repiten cada mes (cuota de autónomos, alquiler,
suscripciones). Aplica a gastos **e** ingresos. Se gestionan en la pestaña
"Recurrentes" de la pantalla de Movimientos (no hay pantalla ni menú aparte).
La generación es **manual**: el botón "Generar ahora" de esa pestaña. No hay
cron ni generación automática (punto pendiente para cuando la app esté hosteada).

1. Se crean desde el formulario de movimiento (al crear o al editar, si aún no
   lo era) marcando "Repetir cada mes". El movimiento sobre el que se marca es
   la primera ocurrencia y queda ligado a la plantilla
   (`movements.recurring_movement_id`).
2. La plantilla guarda `day_of_month` (1..31; si el mes no tiene ese día se usa
   el último del mes), `start_date`, `ends_on` opcional, `next_date` (próxima
   pendiente de generar) y `active` (pausa sin borrar).
3. "Generar ahora" crea un movimiento por cada `next_date` vencida hasta hoy
   (nunca meses futuros ni posteriores a `ends_on`), copiando concepto,
   contraparte, categoría, importes e impuestos. Los movimientos nacen en
   estado `pending` y sin factura: cada recibo se adjunta a su movimiento.
4. Sin duplicados: comprobación previa con `whereDate` e índice único
   `(recurring_movement_id, date)` en `movements` como red de seguridad.
5. Pausar detiene la generación; reactivar continúa desde el primer día
   indicado que aún no haya pasado (sin rellenar los meses de la pausa).
   Borrar la plantilla conserva los movimientos ya generados (`nullOnDelete`).
6. Esquema: tabla `recurring_movements` + columna `movements.recurring_movement_id`.
   Toda la lógica vive en `App\Services\RecurringMovementGenerator`.
