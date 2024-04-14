@extends('principal.principal')

@section('body')




<div class="container">
  <h1>Subir archivo Recibos de Sueldo</h1>

<nav>
    <ul>

        <li>
          <form class="form" action="{{route('subirArchivoRecibos')}}" method="POST" enctype="multipart/form-data">
            @csrf

              
              <div class="input-group">               
 
              </div>

              <div class="grid">
                <input type="file" name="archivoRecibos" id="">
              </div>

              <div class="grid">
                <button type="submit">Subir</button>
              </div>
          </form>
        </li>

    </ul>

</nav>

</div>
<div class="container">



</div>


@endsection 