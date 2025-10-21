@extends('principal.principal')

@section('body')


  <nav>
    <ul>
      <li><h1>Editar:</h1></li>
      <li>
        <h2>{{$usuario->name}}</h2>
      </li>
    </ul>
  </nav>

<div class="container">

  <form method="POST" action="{{route('UpdateUsuario')}}">
    @csrf
    <!-- Grid -->
    <div class="grid">

      <input type="hidden" name="id" value="{{$usuario->id}}">
  
      <!-- Markup example 1: input is inside label -->
      <label for="name">
        Nombre
        <input type="text" id="name" name="name" placeholder="Nombre" required value="{{$usuario->name}}">
      </label>
  
      <label for="dni">
        DNI
        <input type="text" id="dni" name="dni" placeholder="Dni" required value="{{$usuario->dni}}">
      </label>
  
    </div>
  
    <!-- Markup example 2: input is after label -->
    <label for="email">Correo</label>
    <input type="email" id="email" name="email" placeholder="correo" required value="{{$usuario->email}}"> 

    <!-- Select -->
    <label for="role_id">Rol</label>
    <select id="role_id" name="role_id" required>
      @foreach ($roles as $rol)
      @if ($usuario->role_id == $rol->id)
          <option value="{{$rol->id}}" selected>{{$rol->nombre}}</option>            
        @else
          <option value="{{$rol->id}}">{{$rol->nombre}}</option>
        @endif
      @endforeach      
    </select>

    <!-- Select -->
    <label for="empresa_id">Empresa</label>
    <select id="empresa_id" name="empresa_id" required>
      @foreach ($empresas as $emp)
      @if ($usuario->empresa_id == $emp->id)
          <option value="{{$emp->id}}" selected>{{$emp->nombre}}</option>            
        @else
          <option value="{{$emp->id}}">{{$emp->nombre}}</option>
        @endif
      @endforeach      
    </select>
  
    <!-- Button -->
    <button type="submit">Editar</button>
  
  </form>
  <a href="{{ url()->previous() }}" role="button">Volver</a>

</div>
    



@endsection