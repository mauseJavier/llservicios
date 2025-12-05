<nav class="container-fluid">
    <ul>
      <li>
        <a href="{{route('panelServicios')}}" class="contrast" 
          ><strong>LL Servicios</strong></a
        >
      </li>
    </ul>
    <ul>
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
        {{-- <details role="list" dir="rtl">
          <summary aria-haspopup="listbox" role="link" class="contrast">Acciones</summary>
          <ul role="listbox">
            
            <li><a href="{{route('login')}}">Entrar</a></li>
            <li><a href="{{route('registro')}}">Registro</a></li>

          </ul>
        </details> --}}
          <a role="button" href="{{route('login')}}">Entrar</a>
          <a role="button" href="{{route('registro')}}" style="background-color: slategray;">Registro</a>
      </li>
    </ul>
  </nav>