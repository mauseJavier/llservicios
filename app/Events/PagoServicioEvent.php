<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// use App\Models\Pagos;

class PagoServicioEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pago;
    /**
     * Create a new event instance.
     */
    public function __construct($pago)
    {
        // 'id_servicio_pagar'=> $event->pago->idServicioPagar,
        // 'id_usuario'=> $event->pago->idUsuario,
        // 'importe'=> $event->pago->importe,
        $this->pago = $pago;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-PagoServicio'),
        ];
    }
}
