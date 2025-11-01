<div class="container">
    <nav>

        <ul>

          <li>
            
            <img class="imagenLogo" src="{{session('logoEmpresa')}}" alt=""  >
            <a href="{{route('panelServicios')}}" class="contrast" 
              ><strong>LL Servicios</strong></a
            >
          </li>

        </ul>


        <ul>
          <li>

            <details class="dropdown">
              <summary>Menu</summary>
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

          
          <li> 
            <a href="{{route('miPerfil')}}" class="contrast">
              <!--
              tags: [account, avatar, profile, role]
              version: "1.44"
              unicode: "ef68"
              category: System
              -->
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="50"
                height="50"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#5856d6"
                stroke-width="1"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                <path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855" />
              </svg>

            </a>
            
          </li>
          
        </ul>

    </nav>

</div>
