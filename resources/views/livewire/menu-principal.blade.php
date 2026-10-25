<div class="mp-menu-root" @if ($abierto) wire:keydown.escape.window="cerrar" @endif>
    <style>
        body.mp-menu-bloqueado {
            overflow: hidden;
        }

        .mp-menu-trigger {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            width: auto;
            margin: 0;
            padding: .25rem 0;
            border: none;
            background: none;
            box-shadow: none;
            color: var(--pico-primary, #4f46e5);
            font-weight: 600;
            cursor: pointer;
        }

        .mp-menu-trigger:hover,
        .mp-menu-trigger:focus-visible {
            background: none;
            color: var(--pico-primary-hover, #3730a3);
            text-decoration: underline;
        }

        .mp-menu-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            align-items: stretch;
            justify-content: center;
            padding: 0;
            opacity: 0;
            visibility: hidden;
            transition: opacity .18s ease, visibility .18s ease;
        }

        .mp-menu-overlay.mp-menu-overlay--abierto {
            opacity: 1;
            visibility: visible;
        }

        .mp-menu-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(6, 10, 20, .62);
            backdrop-filter: blur(3px);
        }

        .mp-menu-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            background: var(--pico-card-background-color, var(--pico-background-color, #fff));
            color: var(--pico-color, #1f2937);
            transform: translateY(10px);
            transition: transform .18s ease;
            overflow: hidden;
        }

        .mp-menu-overlay--abierto .mp-menu-panel {
            transform: translateY(0);
        }

        .mp-menu-cabecera {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem .85rem;
            border-bottom: 1px solid var(--pico-card-border-color, rgba(128, 128, 128, .22));
        }

        .mp-menu-marca {
            display: flex;
            align-items: center;
            gap: .8rem;
            min-width: 0;
        }

        .mp-menu-marca img,
        .mp-menu-marca .mp-menu-sin-logo {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--pico-primary-focus, rgba(79, 70, 229, .15));
            color: var(--pico-primary, #4f46e5);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }

        .mp-menu-marca-texto {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.2;
        }

        .mp-menu-marca-texto strong {
            font-size: 1.02rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mp-menu-marca-texto small {
            color: var(--pico-muted-color, #6b7280);
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 700;
        }

        .mp-menu-cerrar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.6rem;
            height: 2.6rem;
            margin: 0 0 0 auto;
            padding: 0;
            border: 1px solid var(--pico-card-border-color, rgba(128, 128, 128, .25));
            border-radius: 50%;
            background: transparent;
            color: inherit;
            font-size: 1.05rem;
            cursor: pointer;
        }

        .mp-menu-cerrar:hover,
        .mp-menu-cerrar:focus-visible {
            background: rgba(128, 128, 128, .14);
            color: inherit;
        }

        .mp-menu-buscador {
            position: relative;
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .85rem 1.25rem;
            border-bottom: 1px solid var(--pico-card-border-color, rgba(128, 128, 128, .22));
            background: var(--pico-card-sectioning-background-color, rgba(128, 128, 128, .06));
        }

        .mp-menu-buscador > i {
            position: absolute;
            left: 1.95rem;
            color: var(--pico-muted-color, #6b7280);
            pointer-events: none;
        }

        .mp-menu-buscador input[type="search"] {
            width: 100%;
            margin: 0;
            padding: .7rem 2.6rem .7rem 2.6rem;
            border-radius: 999px;
            font-size: .95rem;
            background: var(--pico-form-element-background-color, var(--pico-card-background-color, #fff));
        }

        .mp-menu-limpiar {
            position: absolute;
            right: 1.65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.9rem;
            height: 1.9rem;
            margin: 0;
            padding: 0;
            border: none;
            border-radius: 50%;
            background: rgba(128, 128, 128, .16);
            color: inherit;
            cursor: pointer;
            font-size: .8rem;
        }

        .mp-menu-cuerpo {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem 1.25rem 2rem;
            overscroll-behavior: contain;
            transition: opacity .15s ease;
        }

        .mp-menu-cuerpo.mp-menu-cargando {
            opacity: .55;
        }

        .mp-menu-categoria + .mp-menu-categoria {
            margin-top: 1.6rem;
        }

        .mp-menu-categoria h2 {
            margin: 0 0 .7rem;
            color: var(--pico-muted-color, #6b7280);
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .mp-menu-grid {
            display: grid;
            gap: .6rem;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        }

        .mp-menu-acceso {
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: .75rem .9rem;
            border: 1px solid var(--pico-card-border-color, rgba(128, 128, 128, .22));
            border-radius: var(--pico-border-radius, .6rem);
            background: var(--pico-card-background-color, #fff);
            color: inherit;
            text-decoration: none;
            transition: border-color .15s ease, transform .15s ease, box-shadow .15s ease;
        }

        .mp-menu-acceso:hover,
        .mp-menu-acceso:focus-visible {
            border-color: var(--pico-primary, #4f46e5);
            color: inherit;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: var(--pico-box-shadow, 0 4px 14px rgba(15, 23, 42, .12));
        }

        .mp-menu-icono {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 2.5rem;
            border-radius: .65rem;
            font-size: 1.05rem;
        }

        .mp-menu-texto {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.25;
        }

        .mp-menu-texto strong {
            font-size: .93rem;
            font-weight: 600;
        }

        .mp-menu-texto small {
            color: var(--pico-muted-color, #6b7280);
            font-size: .76rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mp-menu-flecha {
            margin-left: auto;
            color: var(--pico-muted-color, #9ca3af);
            font-size: .72rem;
            opacity: .7;
        }

        .mp-menu-acceso--peligro {
            border-color: rgba(220, 38, 38, .35);
            background: rgba(220, 38, 38, .07);
        }

        .mp-menu-acceso--peligro:hover,
        .mp-menu-acceso--peligro:focus-visible {
            border-color: #dc2626;
        }

        .mp-menu-vacio {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            padding: 3rem 1rem;
            text-align: center;
            color: var(--pico-muted-color, #6b7280);
        }

        .mp-menu-vacio i {
            font-size: 1.9rem;
            opacity: .5;
        }

        .mp-menu-vacio p {
            margin: 0;
        }

        .mp-menu-vacio button {
            width: auto;
            margin: .25rem 0 0;
        }

        .mp-menu-pie {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .7rem 1.25rem;
            border-top: 1px solid var(--pico-card-border-color, rgba(128, 128, 128, .22));
            background: var(--pico-card-sectioning-background-color, rgba(128, 128, 128, .06));
            color: var(--pico-muted-color, #6b7280);
            font-size: .78rem;
        }

        .mp-menu-pie span {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            min-width: 0;
        }

        .mp-menu-pie strong {
            color: var(--pico-color, #1f2937);
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (min-width: 900px) {
            .mp-menu-overlay {
                align-items: center;
                padding: 2.5rem;
            }

            .mp-menu-panel {
                max-width: 1160px;
                height: min(100%, 880px);
                border: 1px solid var(--pico-card-border-color, rgba(128, 128, 128, .25));
                border-radius: 1rem;
                box-shadow: 0 25px 60px rgba(2, 6, 23, .45);
            }

            .mp-menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .mp-menu-grid {
                grid-template-columns: 1fr;
            }

            .mp-menu-pie {
                flex-direction: column;
                align-items: flex-start;
                gap: .3rem;
            }
        }
    </style>

    <div class="container">
        <nav>
            <ul>
                <li>
                    @if (session('logoEmpresa'))
                        <img class="imagenLogo" src="{{ session('logoEmpresa') }}" alt="Logo de la empresa" style="border-radius: 50%; object-fit: cover;">
                    @endif
                    <a href="{{ route('panelServicios') }}" class="contrast">
                        <strong>{{ $empresa?->nombre ?? 'Panel de Servicios' }}</strong>
                    </a>
                    @if ($whatsappState)
                        <div style="display: inline-flex; align-items: center; margin-left: 8px;">
                            <a href="{{ route('whatsapp.admin') }}" title="Administración de WhatsApp" style="text-decoration: none; cursor: pointer;">
                                <i class="fab fa-whatsapp" style="font-size: 1.4rem; color: {{ $whatsappState === 'open' ? '#22c55e' : ($whatsappState === 'close' ? '#ef4444' : ($whatsappState === 'connecting' ? '#eab308' : '#6b7280')) }};"></i>
                            </a>
                        </div>
                    @endif
                </li>
            </ul>

            <ul>
                <li>
                    <button type="button" class="mp-menu-trigger" wire:click="abrir" aria-haspopup="dialog" aria-controls="mp-menu-overlay">
                        <i class="fas fa-bars"></i>
                        <span>Menú</span>
                    </button>
                </li>
            </ul>
        </nav>
    </div>

    <div
        id="mp-menu-overlay"
        class="mp-menu-overlay {{ $abierto ? 'mp-menu-overlay--abierto' : '' }}"
        role="dialog"
        aria-modal="true"
        aria-label="Menú de accesos"
        aria-hidden="{{ $abierto ? 'false' : 'true' }}"
    >
        <div class="mp-menu-backdrop" wire:click="cerrar"></div>

        <div class="mp-menu-panel">
            <header class="mp-menu-cabecera">
                <div class="mp-menu-marca">
                    @if (session('logoEmpresa'))
                        <img src="{{ session('logoEmpresa') }}" alt="Logo de la empresa">
                    @else
                        <span class="mp-menu-sin-logo"><i class="fas fa-layer-group"></i></span>
                    @endif
                    <div class="mp-menu-marca-texto">
                        <strong>{{ $empresa?->nombre ?? 'Panel de Servicios' }}</strong>
                        <small>Todos los accesos</small>
                    </div>
                </div>

                <button type="button" class="mp-menu-cerrar" wire:click="cerrar" aria-label="Cerrar menú">
                    <i class="fas fa-xmark"></i>
                </button>
            </header>

            <div class="mp-menu-buscador">
                <i class="fas fa-magnifying-glass"></i>
                <input
                    id="mp-menu-busqueda"
                    type="search"
                    wire:model.live.debounce.300ms="busqueda"
                    placeholder="Buscar un acceso…"
                    aria-label="Buscar un acceso"
                    autocomplete="off"
                >
                @if ($busqueda !== '')
                    <button type="button" class="mp-menu-limpiar" wire:click="limpiarBusqueda" aria-label="Limpiar búsqueda">
                        <i class="fas fa-xmark"></i>
                    </button>
                @endif
            </div>

            <div class="mp-menu-cuerpo" wire:loading.class="mp-menu-cargando" wire:target="busqueda">
                @forelse ($categorias as $categoria)
                    <section class="mp-menu-categoria" wire:key="mp-categoria-{{ \Illuminate\Support\Str::slug($categoria['titulo']) }}">
                        <h2>{{ $categoria['titulo'] }}</h2>
                        <div class="mp-menu-grid">
                            @foreach ($categoria['accesos'] as $acceso)
                                @php($color = $acceso['color'] ?? $categoria['color'])
                                <a
                                    href="{{ route($acceso['ruta'], $acceso['parametros'] ?? []) }}"
                                    class="mp-menu-acceso {{ ! empty($acceso['peligro']) ? 'mp-menu-acceso--peligro' : '' }}"
                                    wire:key="mp-acceso-{{ $acceso['ruta'] }}"
                                >
                                    <span class="mp-menu-icono" style="background: {{ $color }}1f; color: {{ $color }};">
                                        <i class="{{ $acceso['icono'] }}"></i>
                                    </span>
                                    <span class="mp-menu-texto">
                                        <strong>{{ $acceso['label'] }}</strong>
                                        @if (! empty($acceso['descripcion']))
                                            <small>{{ $acceso['descripcion'] }}</small>
                                        @endif
                                    </span>
                                    <i class="fas fa-chevron-right mp-menu-flecha"></i>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="mp-menu-vacio">
                        <i class="fas fa-magnifying-glass"></i>
                        <p>No encontramos accesos para “<strong>{{ $busqueda }}</strong>”.</p>
                        <button type="button" class="outline" wire:click="limpiarBusqueda">
                            Limpiar búsqueda
                        </button>
                    </div>
                @endforelse
            </div>

            <footer class="mp-menu-pie">
                <span>
                    <i class="fas fa-circle-user"></i>
                    <strong>{{ auth()->user()?->name }}</strong>
                    @if (auth()->user()?->role?->nombre)
                        · {{ auth()->user()->role->nombre }}
                    @endif
                </span>
                <span><i class="fas fa-keyboard"></i> Esc para cerrar</span>
            </footer>
        </div>
    </div>

    <script>
        (function () {
            if (window.__mpMenuPrincipalInicializado) {
                return;
            }

            window.__mpMenuPrincipalInicializado = true;

            const registrarEventos = function () {
                if (!window.Livewire) {
                    return;
                }

                window.Livewire.on('menu-principal-abierto', function () {
                    document.body.classList.add('mp-menu-bloqueado');

                    window.setTimeout(function () {
                        const input = document.getElementById('mp-menu-busqueda');

                        if (input) {
                            input.focus();
                            input.select();
                        }
                    }, 80);
                });

                window.Livewire.on('menu-principal-cerrado', function () {
                    document.body.classList.remove('mp-menu-bloqueado');

                    const trigger = document.querySelector('.mp-menu-trigger');

                    if (trigger) {
                        trigger.focus();
                    }
                });
            };

            if (window.Livewire) {
                registrarEventos();
            } else {
                document.addEventListener('livewire:init', registrarEventos);
            }
        })();
    </script>
</div>
