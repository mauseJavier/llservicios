
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <title>{{env('APP_NAME')}}</title>
  </head>
  <body>
    @include('principal.menu')

    <div class="container">
      @if ($errors->any())
      <h3>Error:</h3>
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


    <main class="container">

      <h1>Registro</h1>

      <div class="container">
      
          <form method="POST" action="{{route('registrarUsuario')}}">
      
              @csrf
              
              <!-- Grid -->
              <div class="grid">
            
                <!-- Markup example 1: input is inside label -->
                <label for="nombre">
                  Nombre
                  <input type="text" id="nombre" name="nombre" placeholder="Nombre" required>
                </label>
            
                <label for="correo">
                  Correo
                  <input type="email" id="correo" name="correo" placeholder="Correo" required>
                </label>
      
                <label for="contraseña">
                  Contraseña
                  <input type="password" id="contraseña" name="contraseña" placeholder="Contraseña" required>
                </label>
            
              </div>
          
              <!-- Button -->
              <button type="submit">Guardar</button>
            
            </form>
      
      </div>

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
  </body>
</html>



    
