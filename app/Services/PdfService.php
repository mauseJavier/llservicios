<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    /**
     * Genera un PDF a partir de una vista Blade.
     *
     * @param string $view Nombre de la vista Blade
     * @param array $data Datos a pasar a la vista
     * @return \Barryvdh\DomPDF\PDF
     */
    public function fromView(string $view, array $data = [], $formato = 'A4')
    {

        if($formato === 'A4') {
            $formato = [0, 0, 595.28, 841.89]; // Dimensiones en puntos para A4
        } elseif($formato === 'ticket') { 
            // Dimensiones en puntos para Ticket para 80mm de ancho y 200mm de alto
            $formato = [0, 0, 226.77, 567.01]; 
        } else {
            // Formato personalizado en puntos
            $formato = [0, 0, 612, 792]; 
        }
        return Pdf::loadView($view, $data)->setPaper($formato);
    }
}


// modo de uso 
// $pdfService = new PdfService();
// $pdf = $pdfService->fromView('nombre.vista', ['clave' => 'valor']);
// $pdf->stream('nombre_archivo.pdf');


// <?php
// use App\Services\PdfService;

// public function descargarRecibo(PdfService $pdfService)
// {
//     $data = [
//         'nombre' => 'Juan Pérez',
//         'monto' => 1500,
//         // otros datos que necesites pasar a la vista
//     ];

//     $pdf = $pdfService->fromView('recibo', $data);

//     return $pdf->download('recibo.pdf');
// }