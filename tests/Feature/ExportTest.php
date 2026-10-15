<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export(): void
    {
        $this->get(route('movements.export'))->assertRedirect('/login');
        $this->get(route('export.all'))->assertRedirect('/login');
    }

    public function test_movements_csv_export_respects_the_type_filter(): void
    {
        $user = User::factory()->create();

        Movement::create(['user_id' => $user->id, 'date' => '2026-05-15', 'type' => 'income', 'concept' => 'Ingreso A', 'base_amount' => 1000, 'vat_rate' => 21, 'irpf_rate' => 15]);
        Movement::create(['user_id' => $user->id, 'date' => '2026-06-15', 'type' => 'expense', 'concept' => 'Gasto B', 'base_amount' => 200, 'vat_rate' => 21, 'deductible_pct' => 100]);

        $response = $this->actingAs($user)->get(route('movements.export', ['type' => 'income']));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'El CSV debe empezar por el BOM UTF-8.');
        $this->assertStringContainsString('Fecha;Tipo;Concepto', $csv);
        $this->assertStringContainsString('Ingreso A', $csv);
        $this->assertStringContainsString('1000,00', $csv, 'La base debe exportarse con coma decimal.');
        $this->assertStringNotContainsString('Gasto B', $csv);
    }

    public function test_full_backup_zip_contains_the_data_and_the_attached_invoices(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $income = Movement::create([
            'user_id' => $user->id,
            'date' => '2026-05-15',
            'type' => 'income',
            'concept' => 'Servicios de desarrollo',
            'base_amount' => 1000,
            'vat_rate' => 21,
            'irpf_rate' => 15,
        ]);

        $expense = Movement::create([
            'user_id' => $user->id,
            'date' => '2026-08-10',
            'type' => 'expense',
            'concept' => 'Compra de material',
            'base_amount' => 200,
            'vat_rate' => 21,
            'deductible_pct' => 100,
        ]);

        Storage::disk('local')->put('invoices/2026/T2/factura.pdf', 'PDF-INGRESO');
        $income->forceFill(['invoice_path' => 'invoices/2026/T2/factura.pdf'])->save();

        Storage::disk('local')->put('invoices/2026/T3/gasto.pdf', 'PDF-GASTO');
        $expense->forceFill(['invoice_path' => 'invoices/2026/T3/gasto.pdf'])->save();

        $response = $this->actingAs($user)->get(route('export.all'));

        $response->assertOk();

        $path = $response->getFile()->getPathname();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'La copia completa no es un ZIP válido.');

        foreach (['movimientos.csv', 'categorias.csv', 'recurrentes.csv', 'presentaciones.csv', 'LEEME.txt'] as $entry) {
            $this->assertNotFalse($zip->locateName($entry), "Falta {$entry} en la copia.");
        }

        // Facturas clasificadas por año, trimestre y tipo.
        $this->assertNotFalse($zip->locateName('facturas/2026/T2/ingresos/factura.pdf'));
        $this->assertSame('PDF-INGRESO', $zip->getFromName('facturas/2026/T2/ingresos/factura.pdf'));
        $this->assertNotFalse($zip->locateName('facturas/2026/T3/gastos/gasto.pdf'));
        $this->assertSame('PDF-GASTO', $zip->getFromName('facturas/2026/T3/gastos/gasto.pdf'));

        $this->assertStringContainsString('Servicios de desarrollo', $zip->getFromName('movimientos.csv'));

        $zip->close();
    }
}
