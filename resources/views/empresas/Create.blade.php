@extends('principal.principal')

@section('body')


<div class="container">
  <nav>
      <ul>
          <li>
              <h1>Nueva Empresa</h1>
          </li>
          <li>
            {{-- <input type="search" id="search" name="search" placeholder="Search"> --}}
          </li>
      </ul>
      <ul>
          <li>

          </li>
      </ul>
  </nav>

</div>
<div class="container">

    <form method="POST" action="{{route('empresas.store')}}">
        @csrf


        <!-- Grid -->
        <div class="grid">
      
          <!-- Markup example 1: input is inside label -->
          <label for="nombre">
            Nombre Empresa
            <input type="text" id="nombre" name="nombre" placeholder="Nombre Empresa" value="{{old('nombre')}}" required>
          </label>
      
          <label for="cuit">
            Cuit Empresa
            <input type="text" id="cuit" name="cuit" placeholder="Cuit" value="{{old('cuit')}}" required>
          </label>
      
        </div>
      
        <!-- Markup example 2: input is after label -->
        <label for="correo">Correo Electronico</label>
        <input type="email" id="correo" name="correo" placeholder="Correo Electronico" value="{{old('correo')}}" required>
        <small>Opcional.</small>

        <!-- Sección MercadoPago -->
        <h3>Configuración MercadoPago</h3>
        <small>Configura las credenciales de MercadoPago para esta empresa (opcional).</small>
        
        <div class="grid">
          <label for="MP_ACCESS_TOKEN">
            Access Token MercadoPago
            <input type="text" id="MP_ACCESS_TOKEN" name="MP_ACCESS_TOKEN" placeholder="APP_USR-..." value="{{old('MP_ACCESS_TOKEN')}}">
          </label>
      
          <label for="MP_PUBLIC_KEY">
            Public Key MercadoPago  
            <input type="text" id="MP_PUBLIC_KEY" name="MP_PUBLIC_KEY" placeholder="APP_USR-..." value="{{old('MP_PUBLIC_KEY')}}">
          </label>
        </div>

        <div class="grid">
          <label for="MP_USER_ID">
            User ID MercadoPago
            <input type="text" id="MP_USER_ID" name="MP_USER_ID" placeholder="User ID" value="{{old('MP_USER_ID')}}">
          </label>
      
          <label for="client_secret">
            Client Secret
            <input type="text" id="client_secret" name="client_secret" placeholder="Client Secret" value="{{old('client_secret')}}">
          </label>
        </div>

        <div class="grid">
          <label for="client_id">
            Client ID
            <input type="text" id="client_id" name="client_id" placeholder="Client ID" value="{{old('client_id')}}">
          </label>
        </div>

        <!-- Logo de la empresa -->
        <label for="logo">URL del Logo de la Empresa</label>
        <input type="url" id="logo" name="logo" placeholder="https://ejemplo.com/logo.jpg" value="{{old('logo')}}">
        <small>URL del logo de la empresa (opcional).</small>

        <!-- Sección WhatsApp -->
        <h3>Configuración WhatsApp</h3>
        <small>Configura las credenciales de WhatsApp para esta empresa (opcional).</small>
        
        <div class="grid">
          <label for="instanciaWS">
            Instancia WhatsApp
            <input type="text" id="instanciaWS" name="instanciaWS" placeholder="Instancia WhatsApp" value="{{old('instanciaWS')}}">
          </label>
      
          <label for="tokenWS">
            Token WhatsApp
            <input type="text" id="tokenWS" name="tokenWS" placeholder="Token WhatsApp" value="{{old('tokenWS')}}">
          </label>
        </div>
      
        <!-- Button -->
        <button type="submit">Guardar</button>
      
    </form>
    <a href="{{ url()->previous() }}" role="button">Volver</a>


</div>
    
@endsection