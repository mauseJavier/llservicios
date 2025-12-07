@extends('principal.principal')

@section('body')




<div class="container">

    <h1>Agregar Servicio a Cliente</h1>

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
                                <option value="{{$s->id}}" selected  data-precio="{{$s->precio}}" data-precio2="{{$s->precio2 ?? ''}}" data-precio3="{{$s->precio3 ?? ''}}" data-dias-vencimiento="{{$s->diasVencimiento ?? 10}}">{{$s->nombre}}</option>
                            @endforeach
                        </select>
                        
                        <!-- Ayuda de memoria de precios -->
                        <small id="ayuda-precios" style="display: block; margin-top: 5px; color: #666;">
                            <strong>Precios disponibles:</strong> 
                            <span id="info-precio"></span>
                            <span id="info-precio2" style="display: none;"></span>
                            <span id="info-precio3" style="display: none;"></span>
                        </small>
                </div>


                <div>
                        <!-- Select -->
                        <label for="cliente">Clientes</label>
                        <input type="text" id="buscar-cliente" placeholder="Buscar cliente..." autocomplete="off">
                        <select id="cliente" name="cliente" required size="5" style="width: 100%;">
                        
                            @foreach ($clientes as $c)
                                <option value="{{$c->id}}">{{$c->nombre}}</option>
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

                <label for="fecha_vencimiento">
                    Fecha de Vencimiento
                    <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" placeholder="Fecha de Vencimiento">
                </label>

            </div>

            <div class="grid">

                <label for="periodo_servicio">
                    Período del Servicio
                    <input type="month" id="periodo_servicio" name="periodo_servicio" value="{{ \Carbon\Carbon::now()->format('Y-m') }}">
                </label>

                <label for="comentario">
                    Comentario
                    <textarea id="comentario" name="comentario" placeholder="Comentario (opcional)" rows="2"></textarea>
                </label>

            </div>

            <button type="submit" onclick="return confirm('Esta Seguro de Agregar?')">Agregar</button>
        </form>

        <script>
            var select = document.getElementById('servicio');
            var inputPrecio = document.getElementById('precio');
            var inputFechaVencimiento = document.getElementById('fecha_vencimiento');
            
            // Elementos de ayuda de precios
            var infoPrecio = document.getElementById('info-precio');
            var infoPrecio2 = document.getElementById('info-precio2');
            var infoPrecio3 = document.getElementById('info-precio3');

            // Elementos para el buscador de clientes
            var selectCliente = document.getElementById('cliente');
            var inputBuscarCliente = document.getElementById('buscar-cliente');
            var todasLasOpciones = Array.from(selectCliente.options);

            // Función para buscar clientes
            inputBuscarCliente.addEventListener('input', function() {
                var textoBusqueda = this.value.toLowerCase();
                
                // Limpiar el select
                selectCliente.innerHTML = '';
                
                // Filtrar y mostrar solo las opciones que coincidan
                var opcionesFiltradas = todasLasOpciones.filter(function(option) {
                    return option.text.toLowerCase().includes(textoBusqueda);
                });
                
                // Agregar opciones filtradas al select
                opcionesFiltradas.forEach(function(option) {
                    selectCliente.appendChild(option.cloneNode(true));
                });
                
                // Si hay resultados, seleccionar el primero
                if (opcionesFiltradas.length > 0) {
                    selectCliente.selectedIndex = 0;
                }
                
                // Mostrar mensaje si no hay resultados
                if (opcionesFiltradas.length === 0) {
                    var noResultado = document.createElement('option');
                    noResultado.text = 'No se encontraron clientes';
                    noResultado.disabled = true;
                    selectCliente.appendChild(noResultado);
                }
            });

            // Al hacer clic en una opción, limpiar el buscador
            selectCliente.addEventListener('click', function() {
                if (this.value) {
                    inputBuscarCliente.value = this.options[this.selectedIndex].text;
                }
            });

            // Al hacer doble clic, limpiar el buscador para volver a buscar
            inputBuscarCliente.addEventListener('focus', function() {
                this.select();
            });

            // Función para actualizar la ayuda de precios
            function actualizarAyudaPrecios() {
                var selectedOption = select.options[select.selectedIndex];
                var precio = selectedOption.getAttribute('data-precio');
                var precio2 = selectedOption.getAttribute('data-precio2');
                var precio3 = selectedOption.getAttribute('data-precio3');
                
                // Mostrar precio principal
                infoPrecio.textContent = 'Precio: $' + precio;
                infoPrecio.style.display = 'inline';
                
                // Mostrar precio 2 si existe
                if (precio2 && precio2 !== '') {
                    infoPrecio2.textContent = ' | Precio 2: $' + precio2;
                    infoPrecio2.style.display = 'inline';
                } else {
                    infoPrecio2.style.display = 'none';
                }
                
                // Mostrar precio 3 si existe
                if (precio3 && precio3 !== '') {
                    infoPrecio3.textContent = ' | Precio 3: $' + precio3;
                    infoPrecio3.style.display = 'inline';
                } else {
                    infoPrecio3.style.display = 'none';
                }
            }

            // Función para calcular fecha de vencimiento
            function calcularFechaVencimiento() {
                var selectedOption = select.options[select.selectedIndex];
                var precio = selectedOption.getAttribute('data-precio');
                var diasVencimiento = selectedOption.getAttribute('data-dias-vencimiento') || 10;
                
                console.log('Precio seleccionado:', precio);
                console.log('Días de vencimiento:', diasVencimiento);
                
                inputPrecio.value = precio;

                // Calcular fecha de vencimiento (desde hoy)
                var fechaHoy = new Date();
                fechaHoy.setDate(fechaHoy.getDate() + parseInt(diasVencimiento));
                
                var year = fechaHoy.getFullYear();
                var month = String(fechaHoy.getMonth() + 1).padStart(2, '0');
                var day = String(fechaHoy.getDate()).padStart(2, '0');
                
                inputFechaVencimiento.value = year + '-' + month + '-' + day;
                
                // Actualizar ayuda de precios
                actualizarAyudaPrecios();
            }

            // Calcular al cargar la página
            calcularFechaVencimiento();

            // Recalcular cuando cambie el servicio
            select.addEventListener('change', calcularFechaVencimiento);
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