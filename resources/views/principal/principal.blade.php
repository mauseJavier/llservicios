<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css"
    >

    
    <style>
      /* Estilos para el botón redondo */
      .fixed-button {
        position: fixed;
        bottom: 80px; /* Ajusta la distancia desde la parte inferior */
        right: 20px; /* Ajusta la distancia desde la derecha */
        width: 70px; /* Establece el ancho del botón */
        height: 60px; /* Establece la altura del botón */
        background-color: #007BFF; /* Color de fondo del botón */
        color: #fff; /* Color de texto del botón */
        border: none; /* Elimina el borde del botón */
        border-radius: 30%; /* Hace que el botón sea redondo */
        cursor: pointer;
        
        text-align: center; /* Alinea el texto al centro */
        font-size: 20px; /* Tamaño del texto */
        display: flex; /* Utiliza un contenedor flex */
        justify-content: center; /* Centra horizontalmente el contenido */
        align-items: center; /* Centra verticalmente el contenido */

      }
  
      /* Estilos para el menú */
      .menu {
        display: none; /* Inicialmente, el menú está oculto */
        position: fixed;
        bottom: 50px; /* Ajusta la distancia desde la parte inferior */
        right: 10px; /* Ajusta la distancia desde la derecha */
        background-color: #1d2034; /* Color de fondo del menú */
        border: 1px solid #ccc; /* Borde del menú */
        border-radius: 5px; /* Borde redondeado del menú */
        padding: 10px; /* Espaciado interior del menú */
        width: 150px; /* Ancho del menú */
      }


      /* Mobile Devices */
        @media (max-width: 480px) {
          .imagenLogo {
              width: 20%;
            }
        }
                
        /* Low resolution Tablets and iPads */
        @media (min-width: 481px) and (max-width: 767px) {
          .imagenLogo {
              width: 20%;
            }
        }
                
        /* Tablets iPads (Portrait) */
        @media (min-width: 768px) and (max-width: 1024px){
          .imagenLogo {
              width: 10%;
            }
        }
            
        /* Laptops and Desktops */
        @media (min-width: 1025px) and (max-width: 1280px){
          .imagenLogo {
              width: 8%;
            }
        }
            
        /* Big boi Monitors */
        @media (min-width: 1281px) {
          .imagenLogo {
              width: 8%;
            }
        }

    </style>

    <title>{{env('APP_NAME')}}</title>
  </head>
  <body>
      <!-- Contenido de la página... -->
    
        <!-- Botón redondo en la esquina inferior derecha con menú desplegable -->
        {{-- <div class="fixed-button" id="menuButton"><i id="icoButton" class="fas fa-sun"></i></div> --}}
        <button class="fixed-button" id="menuButton"><i id="icoButton" class="fas fa-sun"></i></button>
        <div class="menu" id="dropdownMenu">
          <li><a href="#" data-theme-switcher="auto">Auto</a></li>
          <li><a href="#" data-theme-switcher="light">Luz</a></li>
          <li><a href="#" data-theme-switcher="dark">Oscuro</a></li>
        </div>

    @include('principal.menuAPP')



    <div class="container">
      @if ($errors->any())
      <h1>Error:</h1>
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{$error}}</li>  
        @endforeach
  
      </ul>        
      @endif
  
      @if (session('status'))
  
      <article style="text-align: center;">{{session('status')}}</article>
          
      @endif
    </div>




    <main class="container-fluid">      
        @yield('body')
    </main>

    <script>

            /*!
      * Minimal theme switcher
      *
      * Pico.css - https://picocss.com
      * Copyright 2019-2023 - Licensed under MIT
      */

      const themeSwitcher = {
        // Config
        _scheme: "auto",
        menuTarget: "details[role='list']",
        buttonsTarget: "a[data-theme-switcher]",
        buttonAttribute: "data-theme-switcher",
        rootAttribute: "data-theme",
        localStorageKey: "picoPreferredColorScheme",

        // Init
        init() {
          this.scheme = this.schemeFromLocalStorage;
          this.initSwitchers();
        },

        // Get color scheme from local storage
        get schemeFromLocalStorage() {
          if (typeof window.localStorage !== "undefined") {
            if (window.localStorage.getItem(this.localStorageKey) !== null) {
              return window.localStorage.getItem(this.localStorageKey);
            }
          }
          return this._scheme;
        },

        // Preferred color scheme
        get preferredColorScheme() {
          return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        },

        // Init switchers
        initSwitchers() {
          const buttons = document.querySelectorAll(this.buttonsTarget);
          buttons.forEach((button) => {
            button.addEventListener(
              "click",
              (event) => {
                event.preventDefault();
                // Set scheme
                this.scheme = button.getAttribute(this.buttonAttribute);
                // Close dropdown
                document.querySelector(this.menuTarget).removeAttribute("open");
              },
              false
            );
          });
        },

        // Set scheme
        set scheme(scheme) {
          if (scheme == "auto") {
            this.preferredColorScheme == "dark" ? (this._scheme = "dark") : (this._scheme = "light");
          } else if (scheme == "dark" || scheme == "light") {
            this._scheme = scheme;
          }
          this.applyScheme();
          this.schemeToLocalStorage();
        },

        // Get scheme
        get scheme() {
          return this._scheme;
        },

        // Apply scheme
        applyScheme() {
          document.querySelector("html").setAttribute(this.rootAttribute, this.scheme);
        },

        // Store scheme to local storage
        schemeToLocalStorage() {
          if (typeof window.localStorage !== "undefined") {
            window.localStorage.setItem(this.localStorageKey, this.scheme);
          }
        },
      };

      // Init
      themeSwitcher.init();


    </script>

<script>
  const menuButton = document.getElementById("menuButton");
  const dropdownMenu = document.getElementById("dropdownMenu");
  const icoButton = document.getElementById("icoButton");

  // Agregar un evento de clic al botón para mostrar/ocultar el menú
  menuButton.addEventListener("click", () => {
    if (dropdownMenu.style.display === "block") {
      dropdownMenu.style.display = "none";
    } else {
      dropdownMenu.style.display = "block";
    }
  });

    // Agregar un evento de clic al botón para mostrar/ocultar el menú
    icoButton.addEventListener("click", () => {
    if (dropdownMenu.style.display === "block") {
      dropdownMenu.style.display = "none";
    } else {
      dropdownMenu.style.display = "block";
    }
  });

      // Agregar un evento de clic al documento para ocultar el menú al hacer clic en cualquier lugar
      document.addEventListener("click", (event) => {
      if (event.target !== menuButton && event.target !== dropdownMenu) {
        dropdownMenu.style.display = "none";
      }
    });


</script>


  </body>
</html>