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

    <article>
        <header>Cambiar contraseña</header>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('miPerfil.password') }}">
            @csrf

            <label for="current_password">Contraseña actual</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>

            <label for="password">Nueva contraseña</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>

            <label for="password_confirmation">Confirmar nueva contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>

            <button type="submit">Actualizar contraseña</button>
        </form>
    </article>



</div>
    
@endsection

