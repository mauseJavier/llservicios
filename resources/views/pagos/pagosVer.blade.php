@extends('principal.principal')

@section('body')

{{-- [
  {
    "id": 2,
    "id_servicio_pagar": 6,
    "id_usuario": 1,
    "forma_pago": 1,
    "importe": 888.33,
    "comentario": null,
    "created_at": "2024-02-25 19:32:03",
    "updated_at": "2024-02-25 19:32:03",
    "idServicioPagar": 6,
    "nombreUsuario": "DESMARET JAVIER NICOLAS",
    "Servicio": "Lennie Johnson",
    "Cliente": "Hunter Schneider",
    "idCliente": 3,
    "formaPago": "Efectivo"
  }
] --}}


<div class="container">

    <a role="button" href="{{route('Pagos')}}">Pagos</a>

<figure>

    <article>
        <header>Pago servicio: {{$datos->Servicio}}</header>
        <h1>Pago servicio: {{$datos->Servicio}}</h1>
        
        @if($datos->forma_pago2 && $datos->importe2)
            <h4>💰 Pago dividido en dos formas:</h4>
            
            <div class="grid">
                <div>
                    <h5>Forma de Pago 1: {{$datos->formaPago}}</h5>
                    <p><strong>Importe 1: ${{number_format($datos->importe, 2)}}</strong></p>
                </div>
                <div>
                    <h5>Forma de Pago 2: {{$datos->formaPago2}}</h5>
                    <p><strong>Importe 2: ${{number_format($datos->importe2, 2)}}</strong></p>
                </div>
            </div>
            
            <hr>
            <h4>Total Pagado: <mark>${{number_format($datos->importe + $datos->importe2, 2)}}</mark></h4>
        @else
            <h4>Importe: ${{number_format($datos->importe, 2)}}</h4>
            <h4>Forma de Pago: {{$datos->formaPago}}</h4>
        @endif
        
        @if($datos->comentario)
            <hr>
            <p><strong>Comentario:</strong></p>
            <p style="background: #692525; padding: 10px; border-radius: 5px; font-size: 0.9em;">{{$datos->comentario}}</p>
        @endif
        
        <p>Usuario: {{$datos->nombreUsuario}}</p>
        <p>Fecha: {{\Carbon\Carbon::parse($datos->created_at)->format('d/m/Y H:i:s')}}</p>

        <article>

            <a role="button" href="{{route('PagoPDF',[$datos->id_servicio_pagar])}}">Recibo A4</a>
            <a role="button" href="{{route('PagoPDF',[$datos->id_servicio_pagar,'tamañoPapel'=>'80MM'])}}">Recibo 80mm</a>
        </article>
        
        {{-- Componente de Facturación AFIP --}}
        <hr>
        @livewire('facturacion-afip', ['pagoId' => $datos->id])
        <hr>

        {{-- Envío de recibo/factura por WhatsApp --}}
        @livewire('enviar-comprobantes-whatsapp', ['pagoId' => $datos->id, 'idServicioPagar' => $datos->id_servicio_pagar])
        <hr>

        <article>
            <h5 style="color: #d32f2f;">Zona de peligro</h5>
            <p>Eliminar este pago revierte el servicio relacionado al estado <strong>IMPAGO</strong>.</p>

            @if(!in_array(auth()->user()->role_id, [2, 3]))
                <p style="background: #fff0f0; color: #8a1f1f; padding: 10px; border-radius: 5px;">
                    No tienes permisos para eliminar pagos. Solo Admin o Super.
                </p>
            @elseif($datos->afip_cae)
                <p style="background: #fff0f0; color: #8a1f1f; padding: 10px; border-radius: 5px;">
                    No puedes eliminar este pago porque tiene factura AFIP (CAE: {{$datos->afip_cae}}).
                </p>
            @else
                <form method="POST" action="{{route('pagos.destroy', ['pago' => $datos->id])}}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        style="background: #d32f2f; border: none; color: white; padding: 10px 16px; border-radius: 6px; cursor: pointer;"
                        onclick="return confirm('¿Estás seguro de eliminar este pago? El servicio volverá a estado IMPAGO.');"
                    >
                        Eliminar pago
                    </button>
                </form>
            @endif
        </article>
        <hr>
        
        {{-- <footer>
            <form action="{{route('PagoPDF',[$datos->id_servicio_pagar])}}" method="">
                @csrf
                @method('POST')
            
                <HR></HR>    
                <label for="tamañoPapel">Tamaño Papel</label>
                <select name="tamañoPapel" id="tamañoPapel" aria-label="Select your favorite cuisine..." required>
                  <option value="A4" selected>A4</option>
                  <option value="80MM">80MM</option>    
                </select>
                <HR></HR>          
                
                <button type="submit">Imprimir</button>
            
            
            </form>

        </footer> --}}
      </article>



</figure>





</div>


@endsection 