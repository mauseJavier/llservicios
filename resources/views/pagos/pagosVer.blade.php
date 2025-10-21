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
        <h4>Importe: {{$datos->importe}}</h4>
        <h4>Forma de Pago: {{$datos->formaPago}}</h4>
        <p>Usuario: {{$datos->nombreUsuario}}</p>
        <p>Fecha: {{$datos->created_at}}</p>
        <footer>
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

        </footer>
      </article>



</figure>





</div>


@endsection 