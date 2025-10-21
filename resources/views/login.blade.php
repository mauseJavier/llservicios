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

          <ul>
            @foreach ($errors->all() as $error)
              <li>{{$error}}</li>  
            @endforeach

          </ul>     

      @endif

      @if (session('status'))

      <article>{{session('status')}}</article>
          
      @endif

      <h1>Entrar</h1>

    </div>


    <main class="">

      <div class="">

        <div class="container">

          
        </div>

          <form method="POST" action="{{route('loginUsuario')}} " style=" width: 50%;  position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);">

              @csrf
              
              <!-- Grid -->
              <div class="" >
            
                <label for="email">
                  Correo
                  <input type="email" id="email" name="email" placeholder="Correo" value="{{old('email')}}" required>
                </label>

                <label for="password">
                  Contraseña
                  <input type="password" id="password" name="password" placeholder="Contraseña" required>
                </label>
            
              </div>
          
              <!-- Button -->
              <button type="submit">Entrar</button>
            
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



    
