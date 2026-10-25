<div class="cv-view">

    <style>
        .cv-view {
            --cv-line: rgba(125, 135, 160, .28);
            --cv-soft: rgba(125, 135, 160, .09);
            --cv-muted: var(--pico-muted-color, #8b93a7);
            --cv-card: var(--pico-card-background-color, #1c1f2b);
            --cv-green: #22c55e;
            --cv-blue: #3b82f6;
            --cv-red: #ef4444;
            --cv-amber: #f59e0b;
            --cv-purple: #8b5cf6;
            --cv-cyan: #06b6d4;
        }

        .cv-view * { box-sizing: border-box; }

        /* Encabezado */
        .cv-head {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .9rem;
        }
        .cv-head-brand { display: flex; align-items: center; gap: .85rem; }
        .cv-head-logo {
            width: 56px; height: 56px; flex: 0 0 auto;
            border-radius: 14px; display: grid; place-items: center;
            background: rgba(59, 130, 246, .15);
            border: 1px solid rgba(59, 130, 246, .4);
            color: #60a5fa; font-size: 1.35rem;
        }
        .cv-head h1 { margin: 0; font-size: 1.6rem; line-height: 1.1; }
        .cv-head p { margin: .2rem 0 0; color: var(--cv-muted); font-size: .84rem; }
        .cv-head-actions { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }

        /* Botones */
        .cv-btn {
            display: inline-flex; align-items: center; gap: .45rem;
            padding: .55rem .9rem; margin: 0; width: auto;
            border-radius: 10px; border: 1px solid var(--cv-line);
            background: var(--cv-soft); color: inherit;
            font-size: .84rem; font-weight: 600; line-height: 1.2;
            text-decoration: none; cursor: pointer; white-space: nowrap;
            transition: .15s ease;
        }
        .cv-btn:hover { border-color: rgba(125, 135, 160, .55); transform: translateY(-1px); }
        .cv-btn--primary {
            background: var(--cv-green); border-color: var(--cv-green); color: #05230f;
        }
        .cv-btn--primary:hover { background: #16a34a; border-color: #16a34a; }
        .cv-btn--small { padding: .35rem .6rem; font-size: .76rem; border-radius: 8px; }

        /* Avisos */
        .cv-avisos { display: flex; flex-wrap: wrap; gap: .5rem; }
        .cv-aviso {
            display: flex; align-items: center; gap: .6rem;
            width: auto; margin: 0;
            padding: .5rem .8rem; border-radius: 10px;
            border: 1px solid rgba(34, 197, 94, .35);
            background: rgba(34, 197, 94, .08);
            font-size: .78rem; margin: 0;
        }
        .cv-aviso i { color: #4ade80; font-size: .95rem; }
        .cv-aviso small { display: block; color: var(--cv-muted); font-size: .7rem; }
        .cv-aviso strong { font-size: .82rem; }
        .cv-aviso--warn {
            border-color: rgba(245, 158, 11, .38);
            background: rgba(245, 158, 11, .08);
        }
        .cv-aviso--warn i { color: #fbbf24; }

        /* Tarjetas de resumen */
        .cv-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: .65rem; margin-bottom: .9rem;
        }
        .cv-stat {
            display: flex; align-items: center; gap: .7rem;
            padding: .75rem .85rem; margin: 0;
            border-radius: 12px; border: 1px solid var(--cv-line);
            background: var(--cv-card); color: inherit;
            text-align: left; cursor: pointer; width: 100%;
            transition: .15s ease;
        }
        .cv-stat:hover { transform: translateY(-1px); border-color: rgba(125, 135, 160, .55); }
        .cv-stat.is-active { border-color: currentColor; box-shadow: inset 0 0 0 1px currentColor; }
        .cv-stat-icon {
            width: 40px; height: 40px; flex: 0 0 auto;
            border-radius: 10px; display: grid; place-items: center; font-size: 1rem;
        }
        .cv-stat-body { min-width: 0; display: flex; flex-direction: column; line-height: 1.15; }
        .cv-stat-body small {
            font-size: .68rem; text-transform: uppercase; letter-spacing: .06em;
            color: var(--cv-muted); font-weight: 700;
        }
        .cv-stat-body strong { font-size: 1.42rem; line-height: 1.2; }
        .cv-stat-body span { font-size: .7rem; color: var(--cv-muted); }

        .cv-stat--green { --cv-accent: #22c55e; color: #22c55e; }
        .cv-stat--green .cv-stat-icon { background: rgba(34, 197, 94, .16); color: #22c55e; }
        .cv-stat--blue { --cv-accent: #3b82f6; color: #3b82f6; }
        .cv-stat--blue .cv-stat-icon { background: rgba(59, 130, 246, .16); color: #60a5fa; }
        .cv-stat--red { --cv-accent: #ef4444; color: #ef4444; }
        .cv-stat--red .cv-stat-icon { background: rgba(239, 68, 68, .16); color: #f87171; }
        .cv-stat--amber { --cv-accent: #f59e0b; color: #f59e0b; }
        .cv-stat--amber .cv-stat-icon { background: rgba(245, 158, 11, .16); color: #fbbf24; }
        .cv-stat--purple { --cv-accent: #8b5cf6; color: #8b5cf6; }
        .cv-stat--purple .cv-stat-icon { background: rgba(139, 92, 246, .16); color: #a78bfa; }
        .cv-stat--cyan { --cv-accent: #06b6d4; color: #06b6d4; }
        .cv-stat--cyan .cv-stat-icon { background: rgba(6, 182, 212, .16); color: #22d3ee; }
        .cv-stat-body strong, .cv-stat-body small, .cv-stat-body span { color: inherit; }
        .cv-stat-body small, .cv-stat-body span { color: var(--cv-muted); }

        /* Filtros */
        .cv-filtros {
            display: grid;
            grid-template-columns: minmax(230px, 1.5fr) repeat(4, minmax(145px, 1fr)) auto;
            gap: .7rem; align-items: end;
            padding: .85rem; margin-bottom: .9rem;
            border: 1px solid var(--cv-line); border-radius: 12px;
            background: var(--cv-card);
        }
        .cv-filtro { min-width: 0; }
        .cv-filtro > label {
            display: block; margin: 0 0 .3rem;
            font-size: .67rem; font-weight: 700; letter-spacing: .07em;
            text-transform: uppercase; color: var(--cv-muted);
        }
        .cv-filtro input, .cv-filtro select {
            width: 100%; height: 42px; margin: 0;
            padding: .45rem .7rem; font-size: .84rem;
            border-radius: 9px; border: 1px solid var(--cv-line);
            background: var(--cv-soft); color: inherit;
            box-shadow: none;
        }
        .cv-filtro input:focus, .cv-filtro select:focus {
            border-color: var(--cv-blue); box-shadow: 0 0 0 3px rgba(59, 130, 246, .18);
        }
        .cv-buscador { position: relative; }
        .cv-buscador i {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: var(--cv-muted); font-size: .82rem; pointer-events: none;
        }
        .cv-buscador input { padding-left: 34px; }
        .cv-buscador input::-webkit-search-decoration,
        .cv-buscador input::-webkit-search-cancel-button { -webkit-appearance: none; appearance: none; }
        .cv-segmentado {
            display: flex; gap: .2rem; padding: .2rem;
            border: 1px solid var(--cv-line); border-radius: 9px;
            background: var(--cv-soft); height: 42px;
        }
        .cv-segmentado button {
            flex: 1; margin: 0; padding: .35rem .4rem;
            border: none; border-radius: 7px; background: transparent;
            color: var(--cv-muted); font-size: .76rem; font-weight: 600;
            cursor: pointer; white-space: nowrap;
        }
        .cv-segmentado button.is-active { background: var(--cv-green); color: #05230f; }
        .cv-filtro-acciones { display: flex; gap: .4rem; }

        /* Tabla */
        .cv-tabla-wrap {
            border: 1px solid var(--cv-line); border-radius: 12px;
            overflow: auto; background: var(--cv-card);
        }
        .cv-tabla {
            width: 100%; min-width: 1080px; margin: 0;
            border-collapse: collapse; font-size: .85rem;
        }
        .cv-tabla thead th {
            position: sticky; top: 0; z-index: 2;
            padding: .72rem .65rem; text-align: left; white-space: nowrap;
            background: var(--cv-card);
            border-bottom: 1px solid var(--cv-line);
            font-size: .66rem; font-weight: 700; letter-spacing: .07em;
            text-transform: uppercase; color: var(--cv-muted);
        }
        .cv-tabla tbody td {
            padding: .58rem .65rem; vertical-align: middle;
            border-bottom: 1px solid var(--cv-line);
            background: transparent;
        }
        .cv-tabla tbody tr:hover > td { background: var(--cv-soft); }
        .cv-tabla tbody tr.cv-fila-abierta > td { background: var(--cv-soft); }
        .cv-chevon-col { width: 34px; }
        .cv-acciones-col { width: 150px; text-align: right; }

        .cv-cliente { display: flex; align-items: center; gap: .6rem; }
        .cv-avatar {
            width: 38px; height: 38px; flex: 0 0 auto;
            display: grid; place-items: center;
            border-radius: 10px; border: 1px solid var(--cv-line);
            font-size: .78rem; font-weight: 700;
        }
        .cv-avatar--0 { background: rgba(59, 130, 246, .16); color: #60a5fa; }
        .cv-avatar--1 { background: rgba(34, 197, 94, .16); color: #4ade80; }
        .cv-avatar--2 { background: rgba(139, 92, 246, .16); color: #a78bfa; }
        .cv-avatar--3 { background: rgba(245, 158, 11, .16); color: #fbbf24; }
        .cv-avatar--4 { background: rgba(6, 182, 212, .16); color: #22d3ee; }
        .cv-avatar--5 { background: rgba(236, 72, 153, .16); color: #f472b6; }

        .cv-cliente-nombre {
            display: block; font-weight: 700; font-size: .86rem;
            color: inherit; text-decoration: none; line-height: 1.25;
        }
        .cv-cliente-nombre:hover { color: var(--cv-blue); text-decoration: underline; }
        .cv-muted { color: var(--cv-muted); font-size: .74rem; }
        .cv-muted i { margin-right: .2rem; }

        .cv-servicio { display: flex; align-items: center; gap: .45rem; }
        .cv-servicio strong { display: block; font-size: .82rem; line-height: 1.25; }
        .cv-servicio small { display: block; }
        .cv-punto {
            width: 8px; height: 8px; border-radius: 50%; flex: 0 0 auto;
            background: var(--cv-muted);
        }
        .cv-punto.on { background: var(--cv-green); box-shadow: 0 0 0 3px rgba(34, 197, 94, .18); }
        .cv-punto.off { background: var(--cv-red); }
        .cv-chip {
            display: inline-block; margin-left: .2rem; padding: .1rem .4rem;
            border-radius: 6px; background: var(--cv-soft);
            border: 1px solid var(--cv-line);
            font-size: .68rem; font-weight: 700; color: var(--cv-muted);
        }

        .cv-badge {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .2rem .55rem; border-radius: 999px;
            font-size: .71rem; font-weight: 700; white-space: nowrap;
            border: 1px solid transparent;
        }
        .cv-badge--activo { background: rgba(34, 197, 94, .14); color: #4ade80; border-color: rgba(34, 197, 94, .4); }
        .cv-badge--por-vencer { background: rgba(245, 158, 11, .14); color: #fbbf24; border-color: rgba(245, 158, 11, .4); }
        .cv-badge--vencido { background: rgba(239, 68, 68, .14); color: #f87171; border-color: rgba(239, 68, 68, .4); }
        .cv-badge--sin-servicio { background: rgba(239, 68, 68, .1); color: #f87171; border-color: rgba(239, 68, 68, .32); }
        .cv-badge--segmento {
            background: transparent; font-weight: 600; font-size: .72rem;
            color: var(--cv-muted); border-color: var(--cv-line);
        }

        .cv-vencimiento strong { display: block; font-size: .82rem; }
        .cv-vencimiento small { display: block; }

        .cv-acceso {
            display: inline-flex; align-items: center; gap: .3rem;
            margin: 0; padding: .2rem .5rem; border-radius: 7px;
            border: 1px solid var(--cv-line); background: var(--cv-soft);
            color: inherit; font-size: .74rem; font-weight: 700; cursor: pointer;
        }
        .cv-acceso:hover { border-color: var(--cv-amber); color: #fbbf24; }

        .cv-acciones { display: flex; gap: .3rem; justify-content: flex-end; }
        .cv-icon-btn {
            width: 31px; height: 31px; margin: 0; padding: 0;
            display: grid; place-items: center;
            border-radius: 8px; border: 1px solid var(--cv-line);
            background: var(--cv-soft); color: inherit;
            font-size: .78rem; cursor: pointer; text-decoration: none;
            transition: .15s ease;
        }
        .cv-icon-btn:hover { transform: translateY(-1px); border-color: rgba(125, 135, 160, .6); }
        .cv-icon-btn--edit { background: rgba(59, 130, 246, .18); color: #60a5fa; border-color: rgba(59, 130, 246, .35); }
        .cv-icon-btn--danger { background: rgba(239, 68, 68, .18); color: #f87171; border-color: rgba(239, 68, 68, .35); }
        .cv-icon-btn--warn { background: rgba(245, 158, 11, .18); color: #fbbf24; border-color: rgba(245, 158, 11, .35); }
        .cv-chevron {
            width: 26px; height: 26px; margin: 0; padding: 0;
            display: grid; place-items: center;
            border: none; background: transparent; color: var(--cv-muted);
            cursor: pointer; font-size: .7rem;
        }
        .cv-chevron:hover { color: inherit; }

        /* Detalle expandido */
        .cv-fila-detalle > td { padding: 0 !important; background: var(--cv-soft) !important; }
        .cv-detalle { padding: .9rem; border-top: 2px solid var(--cv-cyan); }
        .cv-detalle-grid {
            display: grid; gap: .7rem;
            grid-template-columns: repeat(auto-fit, minmax(215px, 1fr));
        }
        .cv-detalle-card {
            padding: .75rem; border-radius: 10px;
            border: 1px solid var(--cv-line); background: var(--cv-card);
        }
        .cv-detalle-card h6 {
            margin: 0 0 .5rem; font-size: .66rem; font-weight: 700;
            letter-spacing: .07em; text-transform: uppercase; color: var(--cv-muted);
        }
        .cv-detalle-card p { margin: 0 0 .25rem; font-size: .8rem; }
        .cv-detalle-card p:last-child { margin-bottom: 0; }
        .cv-detalle-lista { display: flex; flex-direction: column; gap: .35rem; }
        .cv-detalle-item {
            display: flex; justify-content: space-between; gap: .5rem;
            font-size: .78rem; padding-bottom: .3rem;
            border-bottom: 1px dashed var(--cv-line);
        }
        .cv-detalle-item:last-child { border-bottom: none; padding-bottom: 0; }
        .cv-detalle-item span:last-child { white-space: nowrap; }

        .cv-tabla-wrap.cv-cargando { opacity: .55; pointer-events: none; }

        .cv-vacio { padding: 2.5rem 1rem; text-align: center; color: var(--cv-muted); }
        .cv-vacio i { display: block; font-size: 1.9rem; margin-bottom: .5rem; opacity: .6; }

        .cv-pie {
            display: flex; flex-wrap: wrap; gap: .75rem;
            align-items: center; justify-content: space-between;
            margin-top: .8rem; font-size: .8rem; color: var(--cv-muted);
        }

        /* Modal de accesos y calendario */
        .cv-modal-head {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 1rem; flex-wrap: wrap;
        }
        .cv-vista-toggle {
            display: flex; gap: .2rem; padding: .2rem;
            border: 1px solid var(--cv-line); border-radius: 9px;
            background: var(--cv-soft);
        }
        .cv-vista-toggle button {
            display: inline-flex; align-items: center; gap: .35rem;
            width: auto; margin: 0; padding: .35rem .7rem;
            border: none; border-radius: 7px; background: transparent;
            color: var(--cv-muted); font-size: .78rem; font-weight: 600;
            cursor: pointer;
        }
        .cv-vista-toggle button.is-active { background: var(--cv-cyan); color: #04222b; }

        .cv-cal-toolbar {
            display: flex; align-items: center; gap: .4rem;
            flex-wrap: wrap; margin-bottom: .6rem;
        }
        .cv-cal-grid {
            display: grid; grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: .28rem;
        }
        .cv-cal-grid--head div {
            text-align: center; padding: .2rem 0;
            font-size: .64rem; font-weight: 700;
            letter-spacing: .06em; text-transform: uppercase;
            color: var(--cv-muted);
        }
        .cv-cal-dia {
            position: relative; width: 100%; min-height: 52px; margin: 0;
            padding: .35rem; display: flex; flex-direction: column;
            align-items: flex-start; justify-content: space-between;
            border: 1px solid var(--cv-line); border-radius: 8px;
            background: var(--cv-soft); color: inherit;
            cursor: pointer; transition: .12s ease;
        }
        .cv-cal-dia:hover { border-color: rgba(125, 135, 160, .6); transform: translateY(-1px); }
        .cv-cal-dia.fuera { opacity: .35; }
        .cv-cal-dia.hoy { outline: 1px dashed var(--cv-cyan); outline-offset: 1px; }
        .cv-cal-dia.seleccionado {
            background: rgba(6, 182, 212, .14);
            border-color: var(--cv-cyan);
            box-shadow: inset 0 0 0 1px var(--cv-cyan);
        }
        .cv-cal-num { font-size: .8rem; font-weight: 700; }
        .cv-cal-cant {
            position: absolute; top: .3rem; right: .3rem;
            min-width: 18px; height: 18px; padding: 0 .3rem;
            display: grid; place-items: center;
            border-radius: 9px; background: var(--cv-cyan); color: #04222b;
            font-size: .64rem; font-weight: 700;
        }
        .cv-cal-detalle {
            margin-top: .75rem; padding: .75rem;
            border: 1px solid var(--cv-line); border-radius: 10px;
            background: var(--cv-soft);
        }
        .cv-cal-detalle-head {
            display: flex; align-items: center; justify-content: space-between;
            gap: .5rem; flex-wrap: wrap;
            margin-bottom: .5rem; padding-bottom: .5rem;
            border-bottom: 1px solid var(--cv-line); font-size: .85rem;
        }
        .cv-cal-evento {
            display: flex; align-items: flex-start; gap: .65rem;
            padding: .4rem 0; border-bottom: 1px dashed var(--cv-line);
        }
        .cv-cal-evento:last-child { border-bottom: none; padding-bottom: 0; }
        .cv-cal-evento small { display: block; font-size: .74rem; }
        .cv-cal-hora {
            flex: 0 0 auto; padding: .18rem .45rem;
            border-radius: 6px; background: rgba(6, 182, 212, .16);
            color: #22d3ee; font-size: .74rem; font-weight: 700;
        }

        .cv-view article.cv-alerta {
            margin: 0 0 .8rem; padding: .7rem .9rem; border-radius: 10px;
            border: 1px solid transparent; font-size: .85rem;
        }
        .cv-alerta--ok { background: rgba(34, 197, 94, .12); border-color: rgba(34, 197, 94, .4); }
        .cv-alerta--error { background: rgba(239, 68, 68, .12); border-color: rgba(239, 68, 68, .4); }

        @media (max-width: 1200px) {
            .cv-filtros { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 760px) {
            .cv-filtros { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .cv-head { align-items: flex-start; }
            .cv-cal-dia { min-height: 42px; padding: .2rem; }
            .cv-cal-cant { top: .15rem; right: .15rem; min-width: 15px; height: 15px; font-size: .6rem; }
        }
    </style>

    {{-- Mensajes de éxito y error --}}
    @if (session()->has('success'))
        <article class="cv-alerta cv-alerta--ok">
            <i class="fas fa-check-circle" style="color:#4ade80;"></i>
            <strong>Éxito:</strong> {{ session('success') }}
        </article>
    @endif

    @if (session()->has('error'))
        <article class="cv-alerta cv-alerta--error">
            <i class="fas fa-exclamation-circle" style="color:#f87171;"></i>
            <strong>Error:</strong> {{ session('error') }}
        </article>
    @endif

    {{-- Encabezado --}}
    <div class="cv-head">
        <div class="cv-head-brand">
            <div class="cv-head-logo"><i class="fas fa-users"></i></div>
            <div>
                <h1>Clientes</h1>
                <p>Gestión de clientes, servicios y acceso al local</p>
            </div>
        </div>

        <div class="cv-avisos">
            <div class="cv-aviso">
                <i class="far fa-calendar-check"></i>
                <div>
                    <small>Próxima notificación</small>
                    <strong>{{ $proximaNotificacion ? $proximaNotificacion->format('d/m/Y') : 'Sin configurar' }}</strong>
                </div>
            </div>
            <button type="button" class="cv-aviso cv-aviso--warn" wire:click="filtrarPorVencimiento('semana')" style="cursor:pointer;">
                <i class="far fa-bell"></i>
                <div style="text-align:left;">
                    <small>Servicios impagos por vencer</small>
                    <strong>{{ $serviciosPorVencer }} {{ $serviciosPorVencer === 1 ? 'servicio por vencer' : 'servicios por vencer' }}</strong>
                    @if ($impagosVencidos > 0)
                        <small>{{ $impagosVencidos }} {{ $impagosVencidos === 1 ? 'ya vencido' : 'ya vencidos' }}</small>
                    @endif
                </div>
            </button>
        </div>

        <div class="cv-head-actions">
            <a href="{{ route('Cliente.create') }}" class="cv-btn cv-btn--primary">
                <i class="fas fa-plus"></i> Nuevo cliente
            </a>
            <a href="{{ route('ImportarClientesCSV') }}" class="cv-btn">
                <i class="fas fa-file-import"></i> Importar
            </a>
            <a href="{{ route('ExportarClientes') }}" class="cv-btn">
                <i class="fas fa-file-export"></i> Exportar
            </a>
        </div>
    </div>

    {{-- Tarjetas de resumen --}}
    @php
        $porcentajeCon = $resumen['total'] > 0 ? round($resumen['con_servicio'] * 100 / $resumen['total']) : 0;
        $porcentajeSin = $resumen['total'] > 0 ? round($resumen['sin_servicio'] * 100 / $resumen['total']) : 0;
    @endphp

    <div class="cv-stats">
        <button type="button" class="cv-stat cv-stat--green {{ $estadoFiltro === '' ? 'is-active' : '' }}" wire:click="limpiarFiltros">
            <span class="cv-stat-icon"><i class="fas fa-users"></i></span>
            <span class="cv-stat-body">
                <small>Total de clientes</small>
                <strong>{{ number_format($resumen['total'], 0, ',', '.') }}</strong>
                <span>Vinculados a la empresa</span>
            </span>
        </button>

        <button type="button" class="cv-stat cv-stat--blue {{ $estadoFiltro === 'con' ? 'is-active' : '' }}" wire:click="filtrarPorEstado('con')">
            <span class="cv-stat-icon"><i class="fas fa-user-check"></i></span>
            <span class="cv-stat-body">
                <small>Con servicio activo</small>
                <strong>{{ number_format($resumen['con_servicio'], 0, ',', '.') }}</strong>
                <span>{{ $porcentajeCon }}% del total</span>
            </span>
        </button>

        <button type="button" class="cv-stat cv-stat--red {{ $estadoFiltro === 'sin' ? 'is-active' : '' }}" wire:click="filtrarPorEstado('sin')">
            <span class="cv-stat-icon"><i class="fas fa-user-slash"></i></span>
            <span class="cv-stat-body">
                <small>Sin servicio</small>
                <strong>{{ number_format($resumen['sin_servicio'], 0, ',', '.') }}</strong>
                <span>{{ $porcentajeSin }}% del total</span>
            </span>
        </button>

        <button type="button" class="cv-stat cv-stat--amber {{ $vencimientoFiltro === 'mes' ? 'is-active' : '' }}" wire:click="filtrarPorVencimiento('mes')">
            <span class="cv-stat-icon"><i class="far fa-clock"></i></span>
            <span class="cv-stat-body">
                <small>Vencen este mes</small>
                <strong>{{ number_format($resumen['vencen_mes'], 0, ',', '.') }}</strong>
                <span>Impagos del mes</span>
            </span>
        </button>

        <a href="{{ route('segmentos') }}" class="cv-stat cv-stat--purple" style="text-decoration:none;">
            <span class="cv-stat-icon"><i class="fas fa-tags"></i></span>
            <span class="cv-stat-body">
                <small>Segmentos</small>
                <strong>{{ number_format($resumen['segmentos'], 0, ',', '.') }}</strong>
                <span>Administrar</span>
            </span>
        </a>

        <div class="cv-stat cv-stat--cyan" style="cursor:default;">
            <span class="cv-stat-icon"><i class="fas fa-door-open"></i></span>
            <span class="cv-stat-body">
                <small>Accesos esta semana</small>
                <strong>{{ number_format($resumen['accesos_semana'], 0, ',', '.') }}</strong>
                <span>Registros de cerradura</span>
            </span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="cv-filtros">
        <div class="cv-filtro">
            <label for="cv-buscar">Buscar cliente</label>
            <div class="cv-buscador">
                <i class="fas fa-search"></i>
                <input id="cv-buscar" type="text" wire:model.live.debounce.400ms="buscarCliente"
                       placeholder="Nombre, titular, correo, teléfono o DNI..." autocomplete="off">
            </div>
        </div>

        <div class="cv-filtro">
            <label for="cv-servicio">Servicio</label>
            <select id="cv-servicio" wire:model.live="servicioFiltro">
                <option value="">Todos los servicios</option>
                @foreach ($servicios as $servicio)
                    <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="cv-filtro">
            <label>Servicio vinculado</label>
            <div class="cv-segmentado">
                <button type="button" class="{{ $estadoFiltro === '' ? 'is-active' : '' }}" wire:click="filtrarPorEstado('todos')">Todos</button>
                <button type="button" class="{{ $estadoFiltro === 'con' ? 'is-active' : '' }}" wire:click="filtrarPorEstado('con')">Con servicio</button>
                <button type="button" class="{{ $estadoFiltro === 'sin' ? 'is-active' : '' }}" wire:click="filtrarPorEstado('sin')">Sin servicio</button>
            </div>
        </div>

        <div class="cv-filtro">
            <label for="cv-segmento">Segmento</label>
            <select id="cv-segmento" wire:model.live="segmentoFiltro">
                <option value="">Todos los segmentos</option>
                @foreach ($segmentos as $segmento)
                    <option value="{{ $segmento->id }}">{{ $segmento->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="cv-filtro">
            <label for="cv-vencimiento">Vencimiento de impagos</label>
            <select id="cv-vencimiento" wire:model.live="vencimientoFiltro">
                <option value="">Todos</option>
                <option value="semana">Próximos 7 días</option>
                <option value="mes">Vencen este mes</option>
                <option value="vencido">Vencidos</option>
            </select>
        </div>

        <div class="cv-filtro cv-filtro-acciones">
            <button type="button" class="cv-btn" wire:click="limpiarFiltros">
                <i class="fas fa-eraser"></i> Limpiar
            </button>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="cv-tabla-wrap" wire:loading.class="cv-cargando" wire:target="buscarCliente,segmentoFiltro,servicioFiltro,vencimientoFiltro,filtrarPorEstado,filtrarPorVencimiento,limpiarFiltros,gotoPage,nextPage,previousPage">
        <table class="cv-tabla">
            <thead>
                <tr>
                    <th class="cv-chevon-col"></th>
                    <th>Cliente</th>
                    <th>Servicio</th>
                    <th>Segmento</th>
                    <th>Estado</th>
                    <th>Vencimiento</th>
                    <th>Accesos</th>
                    <th>Contacto</th>
                    <th class="cv-acciones-col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clientes as $cliente)
                    @php
                        $abierto = (int) $clienteExpandidoId === (int) $cliente->id;
                        $diasVencimiento = $cliente->vencimiento_cercano
                            ? (int) round(now()->startOfDay()->diffInDays($cliente->vencimiento_cercano, false))
                            : null;
                    @endphp

                    <tr class="{{ $abierto ? 'cv-fila-abierta' : '' }}">
                        <td>
                            <button type="button" class="cv-chevron" wire:click="toggleDetalle({{ $cliente->id }})"
                                    title="{{ $abierto ? 'Cerrar detalle' : 'Ver detalle' }}">
                                <i class="fas fa-chevron-{{ $abierto ? 'up' : 'down' }}"></i>
                            </button>
                        </td>

                        <td>
                            <div class="cv-cliente">
                                <span class="cv-avatar cv-avatar--{{ $cliente->id % 6 }}">{{ $this->iniciales($cliente->nombre) }}</span>
                                <div style="min-width:0;">
                                    <a href="{{ route('DetalleCliente', ['clienteId' => $cliente->id]) }}" class="cv-cliente-nombre">
                                        {{ $cliente->nombre }}
                                    </a>
                                    <span class="cv-muted">
                                        <i class="fas fa-map-marker-alt"></i>{{ $cliente->domicilio ?: 'Sin domicilio' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td>
                            @if ($cliente->servicio_principal)
                                <div class="cv-servicio">
                                    <span class="cv-punto {{ $cliente->servicio_principal->activo ? 'on' : 'off' }}"></span>
                                    <div>
                                        <strong>{{ $cliente->servicio_principal->nombre }}</strong>
                                        <small class="cv-muted">${{ number_format($cliente->servicio_principal->precio, 0, ',', '.') }}</small>
                                    </div>
                                </div>
                                @if ($cliente->servicios_ordenados->count() > 1)
                                    <span class="cv-chip">+{{ $cliente->servicios_ordenados->count() - 1 }}</span>
                                @endif
                            @else
                                <span class="cv-muted"><i class="fas fa-minus"></i> Sin servicio</span>
                            @endif
                        </td>

                        <td>
                            @if ($cliente->segmentos->isNotEmpty())
                                @php $segmento = $cliente->segmentos->first(); @endphp
                                <span class="cv-badge cv-badge--segmento"
                                      style="color: {{ $segmento->color ?: 'inherit' }}; border-color: {{ $segmento->color ?: 'var(--cv-line)' }};">
                                    <i class="fas fa-circle" style="font-size:.45rem;"></i> {{ $segmento->nombre }}
                                </span>
                                @if ($cliente->segmentos->count() > 1)
                                    <span class="cv-chip">+{{ $cliente->segmentos->count() - 1 }}</span>
                                @endif
                            @else
                                <span class="cv-muted">Sin segmento</span>
                            @endif
                        </td>

                        <td>
                            <span class="cv-badge cv-badge--{{ $cliente->estado_clase }}">{{ $cliente->estado_label }}</span>
                        </td>

                        <td>
                            @if ($cliente->vencimiento_cercano)
                                <div class="cv-vencimiento">
                                    <strong>
                                        <i class="far fa-calendar"></i> {{ $cliente->vencimiento_cercano->format('d/m/Y') }}
                                        @if ($cliente->vencimiento_es_impago)
                                            <span class="cv-chip" style="margin-left:.15rem;">impago</span>
                                        @endif
                                    </strong>
                                    <small class="cv-muted">
                                        @if (! $cliente->vencimiento_es_impago)
                                            Renovación
                                        @elseif ($diasVencimiento < 0)
                                            Vencido hace {{ abs($diasVencimiento) }} {{ abs($diasVencimiento) === 1 ? 'día' : 'días' }}
                                        @elseif ($diasVencimiento === 0)
                                            Vence hoy
                                        @else
                                            En {{ $diasVencimiento }} {{ $diasVencimiento === 1 ? 'día' : 'días' }}
                                        @endif
                                    </small>
                                </div>
                            @else
                                <span class="cv-muted">—</span>
                            @endif
                        </td>

                        <td>
                            <button type="button" class="cv-acceso" wire:click="verEventosCerradura({{ $cliente->id }})"
                                    title="Ver registros de cerradura">
                                <i class="fas fa-door-open"></i> {{ (int) $cliente->accesos_semana }}
                            </button>
                            <span class="cv-muted" style="display:block;margin-top:.15rem;">
                                {{ $cliente->ultimo_acceso ? \Carbon\Carbon::parse($cliente->ultimo_acceso)->format('d/m H:i') : 'sin registros' }}
                            </span>
                        </td>

                        <td>
                            <strong style="display:block;font-size:.82rem;line-height:1.2;">{{ $cliente->titular ?: $cliente->nombre }}</strong>
                            @if ($cliente->telefono)
                                <a href="https://wa.me/+54{{ $cliente->telefono }}" target="_blank" rel="noopener noreferrer"
                                   class="cv-muted" style="text-decoration:none;">
                                    <i class="fab fa-whatsapp"></i>{{ $cliente->telefono }}
                                </a>
                            @else
                                <span class="cv-muted">Sin teléfono</span>
                            @endif
                        </td>

                        <td>
                            <div class="cv-acciones">
                                <a href="{{ route('DetalleCliente', ['clienteId' => $cliente->id]) }}"
                                   class="cv-icon-btn" title="Ver detalle del cliente">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('Cliente.edit', ['Cliente' => $cliente->id]) }}"
                                   class="cv-icon-btn cv-icon-btn--edit" title="Editar cliente">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <button type="button" class="cv-icon-btn cv-icon-btn--warn"
                                        wire:click="verEventosCerradura({{ $cliente->id }})"
                                        title="Registros de cerradura">
                                    <i class="fas fa-door-closed"></i>
                                </button>
                                <button type="button" class="cv-icon-btn cv-icon-btn--danger"
                                        wire:click="confirmarEliminarCliente({{ $cliente->id }})"
                                        title="Eliminar cliente">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Detalle en línea --}}
                    @if ($abierto && $detalleExpandido)
                        @php
                            $detalleCliente = $detalleExpandido['cliente'];
                            $empresaActualId = auth()->user()->empresa_id;
                            $serviciosDetalle = $detalleCliente->servicios
                                ->filter(fn ($servicio) => (int) $servicio->empresa_id === (int) $empresaActualId)
                                ->sortBy(fn ($servicio) => optional($servicio->pivot->vencimiento)->timestamp ?? PHP_INT_MAX);
                        @endphp
                        <tr class="cv-fila-detalle">
                            <td colspan="9">
                                <div class="cv-detalle">
                                    <div class="cv-detalle-grid">
                                        <div class="cv-detalle-card">
                                            <h6>Contacto</h6>
                                            <p style="display:flex;align-items:center;gap:.5rem;">
                                                <span class="cv-avatar cv-avatar--{{ $detalleCliente->id % 6 }}" style="width:32px;height:32px;">
                                                    {{ $this->iniciales($detalleCliente->nombre) }}
                                                </span>
                                                <strong>{{ $detalleCliente->nombre }}</strong>
                                            </p>
                                            @if ($detalleCliente->titular)
                                                <p class="cv-muted">Titular: {{ $detalleCliente->titular }}</p>
                                            @endif
                                            <p><i class="fas fa-id-card"></i> {{ $detalleCliente->dni ?: 'Sin DNI' }}</p>
                                            <p>
                                                <i class="fas fa-envelope"></i>
                                                @if ($detalleCliente->correo)
                                                    <a href="mailto:{{ $detalleCliente->correo }}">{{ $detalleCliente->correo }}</a>
                                                @else
                                                    Sin correo
                                                @endif
                                            </p>
                                            <p>
                                                <i class="fab fa-whatsapp"></i>
                                                @if ($detalleCliente->telefono)
                                                    <a href="https://wa.me/+54{{ $detalleCliente->telefono }}" target="_blank" rel="noopener noreferrer">{{ $detalleCliente->telefono }}</a>
                                                @else
                                                    Sin teléfono
                                                @endif
                                            </p>
                                            <p class="cv-muted"><i class="fas fa-map-marker-alt"></i> {{ $detalleCliente->domicilio ?: 'Sin domicilio' }}</p>
                                        </div>

                                        <div class="cv-detalle-card">
                                            <h6>Servicios vinculados</h6>
                                            @if ($serviciosDetalle->isEmpty())
                                                <p class="cv-muted">Este cliente no tiene servicios vinculados.</p>
                                            @else
                                                <div class="cv-detalle-lista">
                                                    @foreach ($serviciosDetalle->take(4) as $servicio)
                                                        <div class="cv-detalle-item">
                                                            <span>
                                                                <span class="cv-punto {{ $servicio->activo ? 'on' : 'off' }}" style="display:inline-block;margin-right:.3rem;"></span>
                                                                {{ $servicio->nombre }}
                                                            </span>
                                                            <span class="cv-muted">
                                                                {{ $servicio->pivot->vencimiento ? \Carbon\Carbon::parse($servicio->pivot->vencimiento)->format('d/m/Y') : 'Sin fecha' }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if ($serviciosDetalle->count() > 4)
                                                    <p class="cv-muted" style="margin-top:.4rem;">+{{ $serviciosDetalle->count() - 4 }} servicio(s) más</p>
                                                @endif
                                            @endif
                                        </div>

                                        <div class="cv-detalle-card">
                                            <h6>Acceso al local</h6>
                                            <p><strong>{{ $detalleExpandido['accesos_semana'] }}</strong> {{ $detalleExpandido['accesos_semana'] === 1 ? 'acceso' : 'accesos' }} esta semana</p>
                                            <p class="cv-muted">
                                                Último registro:
                                                {{ $detalleExpandido['ultimo_acceso'] ? \Carbon\Carbon::parse($detalleExpandido['ultimo_acceso'])->format('d/m/Y H:i') : 'sin registros' }}
                                            </p>
                                            <button type="button" class="cv-btn cv-btn--small" wire:click="verEventosCerradura({{ $detalleCliente->id }})">
                                                <i class="fas fa-list"></i> Ver registros
                                            </button>
                                        </div>

                                        <div class="cv-detalle-card">
                                            <h6>Últimos ingresos</h6>
                                            @if ($detalleExpandido['pagos']->isEmpty())
                                                <p class="cv-muted">Sin pagos registrados.</p>
                                            @else
                                                <div class="cv-detalle-lista">
                                                    @foreach ($detalleExpandido['pagos'] as $pago)
                                                        <div class="cv-detalle-item">
                                                            <span>{{ $pago->servicio_nombre }}</span>
                                                            <span>
                                                                <strong>${{ number_format((float) $pago->importe + (float) ($pago->importe2 ?? 0), 0, ',', '.') }}</strong>
                                                                <span class="cv-muted" style="display:block;">{{ \Carbon\Carbon::parse($pago->created_at)->format('d/m/Y') }}</span>
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div style="display:flex;gap:.4rem;justify-content:flex-end;margin-top:.7rem;flex-wrap:wrap;">
                                        <a href="{{ route('DetalleCliente', ['clienteId' => $detalleCliente->id]) }}" class="cv-btn cv-btn--small">
                                            <i class="fas fa-external-link-alt"></i> Ver ficha completa
                                        </a>
                                        <a href="{{ route('Cliente.edit', ['Cliente' => $detalleCliente->id]) }}" class="cv-btn cv-btn--small">
                                            <i class="fas fa-pen"></i> Editar
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="cv-vacio">
                                <i class="fas fa-users-slash"></i>
                                No se encontraron clientes con los filtros seleccionados.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if ($clientes->count() > 0)
        <div class="cv-pie">
            <div>
                Mostrando <strong>{{ $clientes->firstItem() }}</strong> a <strong>{{ $clientes->lastItem() }}</strong>
                de <strong>{{ $clientes->total() }}</strong> clientes
            </div>
            <div>
                {{ $clientes->links('vendor.pagination.custom') }}
            </div>
        </div>
    @endif

    {{-- Modal de Confirmación --}}
    @if ($mostrarModalConfirmacion && $clienteAEliminar)
        <dialog open>
            <article>
                <header>
                    <h3>⚠️ Confirmar Eliminación</h3>
                </header>
                <p>
                    <strong>¿Está seguro de que desea eliminar al cliente?</strong>
                </p>
                <p>
                    <strong>Cliente:</strong> {{ $clienteAEliminar->nombre }}<br>
                    <strong>DNI:</strong> {{ $clienteAEliminar->dni }}
                </p>
                <div style="background-color: #fff3cd; padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                    <strong style="color: #856404;">⚠️ ADVERTENCIA:</strong>
                    <p style="color: #856404; margin: 0.5rem 0 0 0;">
                        Esta acción eliminará de forma <strong>permanente</strong>:
                    </p>
                    <ul style="color: #856404; margin: 0.5rem 0;">
                        <li>El cliente</li>
                        <li>Todos los servicios pagos del cliente</li>
                        <li>Todos los servicios impagos del cliente</li>
                        <li>Todos los pagos registrados</li>
                        <li>Las vinculaciones del cliente con servicios</li>
                    </ul>
                    <p style="color: #856404; margin: 0.5rem 0 0 0;">
                        <strong>Esta acción NO se puede deshacer.</strong>
                    </p>
                </div>
                <footer style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button wire:click="cancelarEliminacion" class="secondary">Cancelar</button>
                    <button wire:click="eliminarCliente" style="background-color: #dc3545;">Sí, Eliminar Definitivamente</button>
                </footer>
            </article>
        </dialog>
    @endif

    {{-- Modal de Registros de Cerradura --}}
    @if ($mostrarModalEventos && $clienteEventos)
        <dialog open>
            <article style="max-width: 960px; width: 96%;">
                <header>
                    <div class="cv-modal-head">
                        <div>
                            <h3 style="margin:0;">🔓 Registros de cerradura</h3>
                            <p class="cv-muted" style="margin:.2rem 0 0;">
                                {{ $clienteEventos->nombre }}
                                @if ($clienteEventos->dni)
                                    — DNI: {{ $clienteEventos->dni }}
                                @endif
                            </p>
                        </div>
                        <div class="cv-vista-toggle">
                            <button type="button" class="{{ $vistaEventos === 'calendario' ? 'is-active' : '' }}"
                                    wire:click="cambiarVista('calendario')">
                                <i class="fas fa-calendar-days"></i> Calendario
                            </button>
                            <button type="button" class="{{ $vistaEventos === 'lista' ? 'is-active' : '' }}"
                                    wire:click="cambiarVista('lista')">
                                <i class="fas fa-list"></i> Lista
                            </button>
                        </div>
                    </div>
                </header>

                @if ($vistaEventos === 'calendario')
                    <div class="cv-cal">
                        <div class="cv-cal-toolbar">
                            <button type="button" class="cv-icon-btn" wire:click="mesAnterior" title="Mes anterior">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <strong style="min-width:135px;text-align:center;">{{ $this->nombreMesCalendario() }}</strong>
                            <button type="button" class="cv-icon-btn" wire:click="mesSiguiente" title="Mes siguiente">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <button type="button" class="cv-btn cv-btn--small" wire:click="irMesActual">Hoy</button>
                            <span class="cv-muted" style="margin-left:auto;">
                                {{ $eventosCerradura->count() }} {{ $eventosCerradura->count() === 1 ? 'acceso' : 'accesos' }} en el mes
                            </span>
                        </div>

                        <div class="cv-cal-grid cv-cal-grid--head">
                            @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $diaSemana)
                                <div>{{ $diaSemana }}</div>
                            @endforeach
                        </div>

                        <div class="cv-cal-grid">
                            @foreach ($diasCalendario as $dia)
                                <button type="button" wire:key="cal-{{ $dia['fecha'] }}"
                                        class="cv-cal-dia {{ $dia['del_mes'] ? '' : 'fuera' }} {{ $dia['hoy'] ? 'hoy' : '' }} {{ $diaSeleccionado === $dia['fecha'] ? 'seleccionado' : '' }}"
                                        wire:click="seleccionarDia('{{ $dia['fecha'] }}')"
                                        title="{{ $dia['cantidad'] }} acceso(s) el {{ \Carbon\Carbon::parse($dia['fecha'])->format('d/m/Y') }}">
                                    <span class="cv-cal-num">{{ $dia['dia'] }}</span>
                                    @if ($dia['cantidad'] > 0)
                                        <span class="cv-cal-cant">{{ $dia['cantidad'] }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <div class="cv-cal-detalle">
                            <div class="cv-cal-detalle-head">
                                <strong>
                                    <i class="far fa-calendar"></i>
                                    {{ $diaSeleccionado ? \Carbon\Carbon::parse($diaSeleccionado)->format('d/m/Y') : 'Sin día seleccionado' }}
                                </strong>
                                <span class="cv-muted">
                                    {{ count($eventosDia) }} {{ count($eventosDia) === 1 ? 'acceso' : 'accesos' }}
                                </span>
                            </div>

                            @forelse ($eventosDia as $evento)
                                <div class="cv-cal-evento">
                                    <span class="cv-cal-hora">{{ $evento['hora'] }}</span>
                                    <div style="min-width:0;">
                                        <strong>{{ $evento['nombre_cerradura'] }}</strong>
                                        <small class="cv-muted">
                                            {{ $evento['tipo'] ?? 'Sin tipo' }}
                                            · {{ $evento['dispositivo'] ?? 'Sin dispositivo' }}
                                        </small>
                                        @if (! empty($evento['datos_extra']))
                                            <small class="cv-muted" style="word-break:break-all;">
                                                {{ json_encode($evento['datos_extra'], JSON_UNESCAPED_UNICODE) }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="cv-muted" style="margin:.5rem 0 0;">Sin accesos registrados este día.</p>
                            @endforelse
                        </div>
                    </div>
                @else
                    @if ($eventosCerradura->isEmpty())
                        <p class="cv-muted">Este cliente no tiene registros de cerradura en {{ $this->nombreMesCalendario() }}.</p>
                    @else
                        <div class="overflow-auto" style="max-height: 60vh;">
                            <table class="cv-tabla" style="min-width: 0;">
                                <thead>
                                    <tr>
                                        <th>Fecha y hora</th>
                                        <th>Cerradura</th>
                                        <th>Tipo</th>
                                        <th>Dispositivo</th>
                                        <th>Datos extra</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($eventosCerradura as $evento)
                                        <tr>
                                            <td>{{ $evento->fecha_hora?->format('d/m/Y H:i:s') }}</td>
                                            <td>{{ $evento->nombre_cerradura }}</td>
                                            <td>{{ $evento->tipo ?? '-' }}</td>
                                            <td>{{ $evento->dispositivo_id ?? '-' }}</td>
                                            <td>
                                                @if ($evento->datos_extra)
                                                    <code style="font-size: 0.75rem;">{{ json_encode($evento->datos_extra, JSON_UNESCAPED_UNICODE) }}</code>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="cv-muted" style="margin-top: .5rem;">
                            Mostrando {{ $eventosCerradura->count() }} registro(s) de {{ $this->nombreMesCalendario() }}.
                        </p>
                    @endif
                @endif

                <footer style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button wire:click="cerrarModalEventos" class="secondary">Cerrar</button>
                </footer>
            </article>
        </dialog>
    @endif

</div>
