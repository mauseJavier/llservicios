<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class ComprobantePagoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Datos del pago para mostrar en el correo y generar el PDF
     *
     * @var array
     */
    public $datos;

    /**
     * Create a new message instance.
     *
     * @param array $datos Datos del pago
     */
    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Comprobante de Pago - ' . ($this->datos['nombreServicio'] ?? 'Servicio'),
            from: new Address('notificacion@llservicios.ar', 'LLServicios.ar'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'Correos.ComprobantePagoMail',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Generar el PDF con los datos del pago
        $pdf = Pdf::loadView('pdf.comprobante_pago', $this->datos)->setPaper('a6');
        
        // Nombre del archivo con información relevante
        $nombreArchivo = 'comprobante_pago_' 
            . ($this->datos['mp_payment_id'] ?? $this->datos['nombreCliente'] ?? 'cliente') 
            . '.pdf';
        
        return [
            Attachment::fromData(fn () => $pdf->output(), $nombreArchivo)
                ->withMime('application/pdf'),
        ];
    }
}
