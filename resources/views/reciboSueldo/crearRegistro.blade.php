@extends('principal.principal')

@section('body')




<div class="container">

    @if (Session::has('mensaje'))
      <article>
        <div >{{ Session::get('mensaje') }}</div>
      </article>
    @endif

  <h1>Formato Recibos de Sueldo</h1>
  <h3>Ingrese los nombres de las columnas de su archivo de registro</h3>




  
          <form class="form" action="{{route('formatoRegistroStore')}}" method="POST">
            @csrf
            @method('POST')

            <fieldset >
                <label >
                  Tipo de Registro
                  <select name="tipo" aria-label="Seleccione tipo" required >
                    <option value="ingresos">Ingresos</option>
                    <option value="deducciones">Deducciones</option>
                    <option value="total">Total</option>
                  </select>
                </label>

                <label>
                  Codigo
                  <input type="text" name="codigo" placeholder="Nombre de Columna Codigo" aria-label="Codigo" />
                </label>
                <label>
                    Descripcion
                    <input type="text" name="descripcion" placeholder="Nombre de Columna Descripcion"  />
                  </label>
                  <label>
                    Cantidad
                    <input type="text" name="cantidad" placeholder="Nombre de Columna Cantidad"  />
                  </label>
                  <label>
                    Importe
                    <input type="text" name="importe" placeholder="Nombre de Columna Importe"  />
                  </label>
              </fieldset>          
            


            <button type="submit">Crear</button>


          </form>
    



        



</div>



@endsection 