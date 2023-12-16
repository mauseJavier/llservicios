<nav class="container-fluid">
    <ul>
      <li>
        <a href="{{route('panel')}}" class="contrast" 
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
        <details role="list" dir="rtl">
          <summary aria-haspopup="listbox" role="link" class="contrast">Tema</summary>
          <ul role="listbox">
            <li><a href="#" data-theme-switcher="auto">Auto</a></li>
            <li><a href="#" data-theme-switcher="light">Luz</a></li>
            <li><a href="#" data-theme-switcher="dark">Oscuro</a></li>
          </ul>
        </details>
      </li> --}}
      <li>
        <details role="list" dir="rtl">
          <summary aria-haspopup="listbox" role="link" class="contrast">Menu</summary>
          <ul role="listbox">
            <li><a href="{{route('panel')}}">Panel</a></li>

            @if (Auth::User()->role->nombre == 'Super' || 
                 Auth::User()->role->nombre == 'Admin')
              <li><a href="{{route('Cliente.index')}}">Clientes</a></li>
              <li><a href="{{route('Servicios.index')}}">Servicios</a></li>
              <li><a href="{{route('ServiciosImpagos')}}">S.Pagos-Impagos</a></li>
              <li><a href="{{route('Pagos')}}">Pagos</a></li>
           
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