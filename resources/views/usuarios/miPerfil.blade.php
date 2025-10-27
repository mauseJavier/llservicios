@extends('principal.principal')

@section('body')

<div class="container">


    <nav class="">
        <ul>
            <li><h1>Mi Perfil</h1></li>
            {{-- <li>
                <!-- Dropdown -->
                <details role="list">
                    <summary aria-haspopup="listbox">Dropdown</summary>
                    <ul role="listbox">
                    <li><a>Action</a></li>
                    <li><a>Another action</a></li>
                    <li><a>Something else here</a></li>
                    </ul>
                </details>
            </li>
            <li>
                <!-- Select -->
                <select>
                    <option value="" disabled selected>Select</option>
                    <option>…</option>
                </select>
            </li> --}}
        </ul>

    </nav>

    <article>
        <header>{{Auth::User()->name}}</header>
        {{-- {{Auth::User()}} <br> --}}
        <strong>ID:</strong> {{Auth::User()->id}} <br>
        <strong>Nombre:</strong> {{Auth::User()->name}} <br>
        <strong>Correo:</strong> {{Auth::User()->email}} <br>
        <strong>DNI:</strong> {{Auth::User()->dni}} <br>
        <strong>email_verified_at:</strong> {{Auth::User()->email_verified_at}} <br>
        <strong>Rol:</strong> {{$rol->nombre}} <br>
        <strong>Empresa:</strong> {{$empresa->nombre}} <br>
        <strong>Creado:</strong> {{Auth::User()->created_at}} <br>

    </article>



</div>
    
@endsection

