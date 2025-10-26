<nav class="container">
    <ul>
      <img class="imagenLogo" src="{{session('logoEmpresa')}}" alt=""  >
      <li>
        
        <a href="{{route('panelServicios')}}" class="contrast" 
          ><strong>LL Servicios</strong></a
        >
      </li>

    </ul>
    <ul>
      <li>
        <a href="{{route('miPerfil')}}" class="contrast">
          {{Auth::User()->name}}</a>
      </li>
      {{-- <li>
        <details class="dropdown">
          <summary class="contrast">Tema</summary>
          <ul>
            <li><a href="#" data-theme-switcher="auto">Auto</a></li>
            <li><a href="#" data-theme-switcher="light">Luz</a></li>
            <li><a href="#" data-theme-switcher="dark">Oscuro</a></li>
          </ul>
        </details>
      </li> --}}
      <li>
        <details class="dropdown">
          <summary class="contrast">Menu</summary>
          <ul>
            <li><a href="{{route('panelServicios')}}">Panel</a></li>
            {{-- <li><a href="{{route('reciboSueldo')}}">Recibos Sueldo</a></li> --}}

            @if (Auth::User()->role->nombre == 'Super' || 
                 Auth::User()->role->nombre == 'Admin')
              <li><a href="{{route('Cliente.index')}}">Clientes</a></li>
              <li><a href="{{route('Servicios.index')}}">Servicios</a></li>
              <li><a href="{{route('Grilla')}}">Grilla Clientes</a></li>
              <li><a href="{{route('Pagos', ['fecha_inicio' => date('Y-m-d'), 'fecha_fin' => date('Y-m-d')])}}">Pagos</a></li>
              <li><a href="{{route('ServiciosImpagos')}}">Impagos</a></li>

              <li><a href="{{route('expenses.index')}}">Gastos</a></li>
              <li><a href="{{route('cierre-caja')}}">Cierre de Caja</a></li>
           
            @endif



            @if (Auth::User()->role->nombre == 'Super')
              <li><a href="{{route('usuarios')}}">Usuarios</a></li>
              <li><a href="{{route('empresas.index')}}">Empresas</a></li>
            @endif
            <li><a href="{{route('logout')}}" style="border-radius: 10px; background-color:red;" >Salir</a></li>

            
          </ul>
        </details>
      </li>
    </ul>
  </nav>

  <style> .{border-radius: 50%;}</style>