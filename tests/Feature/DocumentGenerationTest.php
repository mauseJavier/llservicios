<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Services\PdfService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_service_genera_un_pdf_valido(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Empresa PDF',
            'cuit' => '12345678901',
            'correo' => 'pdf@test.com',
        ]);

        $datos = (object) [
            'created_at' => now()->format('d/m/Y H:i'),
            'Cliente' => 'Cliente Test',
            'Servicio' => 'Servicio Test',
            'importe' => 1500.00,
            'comentario' => 'Pago de prueba',
            'nombreUsuario' => 'Usuario Test',
            'formaPago' => 'Efectivo',
            'forma_pago2' => null,
            'formaPago2' => null,
            'importe2' => null,
        ];

        $pdf = (new PdfService)->fromView('pdf.pagoPDF', [
            'datos' => $datos,
            'empresa' => $empresa,
        ], 'ticket');

        $contenido = $pdf->output();

        $this->assertStringStartsWith('%PDF', $contenido);
        $this->assertGreaterThan(1000, strlen($contenido));
    }

    public function test_se_genera_un_qr_png(): void
    {
        $writer = new PngWriter;
        $qr = new QrCode('https://www.arca.gob.ar/fe/qr/?p=prueba');

        $result = $writer->write($qr);
        $dataUri = $result->getDataUri();

        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
        $this->assertGreaterThan(100, strlen($dataUri));
    }
}
