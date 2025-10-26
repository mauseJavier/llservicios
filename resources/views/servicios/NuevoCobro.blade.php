@extends('principal.principal')

@section('body')




<div class="container">

    <h1>Nuevo Cobro Manual</h1>

</div>


<div class="container">

    {{-- {
        "id": 180,
        "nombre": "Dr. Josiah Hagenes MD",
        "descripcion": "Miss Zetta Friesen Una descripcion de prueba",
        "precio": 8905.17,
        "tiempo": "mes",
        "empresa_id": 1,
        "created_at": "2023-12-21 17:11:53",
        "updated_at": "2023-12-21 17:11:53"
      }, --}}

        <form action="{{route('AgregarNuevoCobro')}}" method="POST">
            @method('POST')
            @csrf
            

              <!-- Grid -->
            <div class="grid">

                <div>
                        <!-- Select -->
                        <label for="servicio">Servicios</label>
                        <select id="servicio" name="servicio" required>
                        
                            @foreach ($servicios as $s)
                                <option value="{{$s->id}}" selected  data-precio="{{$s->precio}}">{{$s->nombre}}</option>
                            @endforeach
                        </select>
                </div>


                <div>
                        <!-- Select -->
                        <label for="cliente">Clientes</label>
                        <select id="cliente" name="cliente" required>
                        
                            @foreach ($clientes as $c)
                                <option value="{{$c->id}}" selected>{{$c->nombre}}</option>
                            @endforeach
                        </select>
                </div>



            </div>

            <div class="grid">

                <!-- Markup example 1: input is inside label -->
                <label for="precio">
                    Precio
                    <input type="number" step="any" id="precio" name="precio" placeholder="Precio" required value="0" >
                </label>

                    <!-- Markup example 1: input is inside label -->
                <label for="cantidad">
                    Cantidad
                    <input type="number" id="cantidad" name="cantidad" placeholder="Cantidad" required value="1" min="0.5" step="0.5">
                </label>

                <label for="fecha">
                    Fecha de Cobro
                    <input type="date" id="fecha" name="fecha" placeholder="Fecha de Cobro" required value="{{date('Y-m-d')}}">
                </label>


            </div>

            <button type="submit" onclick="return confirm('Esta Seguro de Agregar?')">Agregar</button>
        </form>

        <script>
            var select = document.getElementById('servicio');
            var inputPrecio = document.getElementById('precio');
            select.addEventListener('change', function() {
            var selectedOption = this.options[select.selectedIndex];
            var precio = selectedOption.getAttribute('data-precio');
            console.log('Precio seleccionado:', precio);
            inputPrecio.value = precio;
            });


        </script>

{{-- <div>

    @if (method_exists($servicios, 'currentPage'))   

        //PAGINACION 
        <nav> 
        <ul>
            <li><strong>Pag. {{$servicios->currentPage()}} de: {{$servicios->lastPage()}} , Total Res.: {{$servicios->total()}}</strong></li>
        </ul>
    
        <ul>
            <li><a href=" {{$servicios->previousPageUrl()}}" role="button">Anterior</a></li>
                @if ($servicios->currentPage()-1 != 0)
                <li>
                    <a href="{{$servicios->url($servicios->currentPage()-1)}}">{{$servicios->currentPage()-1}}</a> 
                </li>
                @endif
                <li>
                    <strong>
                    <a href="{{$servicios->url($servicios->currentPage())}}">{{$servicios->currentPage()}}</a>
                    </strong>            
                </li>
                @if (($servicios->currentPage() +1 ) < round($servicios->total()/$servicios->perPage())+1)
                <li>
                    <a href="{{$servicios->url($servicios->currentPage() +1)}}">{{$servicios->currentPage() +1}}</a>
                </li>
                @endif
    
            <li><a href="{{$servicios->nextPageUrl()}}" role="button">Siguiente</a></li>
        </ul>
        </nav>
    
    
    @endif
  
</div> --}}


</div>


@endsection 