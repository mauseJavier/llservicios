<?php

namespace App\Livewire\VerCliente;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Pagos;
use App\Models\RegistroCerradura;
use App\Models\Segmento;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class VerCliente extends Component
{
    use WithPagination;

    public $idCliente;

    // Filtros de la grilla
    public $buscarCliente = '';

    public $segmentoFiltro = '';

    public $servicioFiltro = '';

    public $estadoFiltro = '';        // '' | 'con' | 'sin'

    public $vencimientoFiltro = '';   // '' | 'mes' | 'semana' | 'vencido'

    // Detalle expandido de una fila
    public $clienteExpandidoId = null;

    public $detalleExpandido = null;

    // Eliminación
    public $clienteAEliminar = null;

    public $mostrarModalConfirmacion = false;

    // Registros de cerradura
    public $clienteEventos = null;

    public $eventosCerradura = [];

    public $mostrarModalEventos = false;

    // Calendario de accesos
    public $vistaEventos = 'calendario';

    public $mesCalendario = null;

    public $diaSeleccionado = null;

    public $diasCalendario = [];

    public $eventosMes = [];

    public $eventosDia = [];

    public function mount($idCliente = null)
    {
        // Los clientes se cargan mediante paginación en el método render()
    }

    public function updatingBuscarCliente()
    {
        $this->resetPage();
        $this->cerrarDetalle();
    }

    public function updatingSegmentoFiltro()
    {
        $this->resetPage();
        $this->cerrarDetalle();
    }

    public function updatingServicioFiltro()
    {
        $this->resetPage();
        $this->cerrarDetalle();
    }

    public function updatingEstadoFiltro()
    {
        $this->resetPage();
        $this->cerrarDetalle();
    }

    public function updatingVencimientoFiltro()
    {
        $this->resetPage();
        $this->cerrarDetalle();
    }

    /**
     * Limpia todos los filtros de la grilla.
     */
    public function limpiarFiltros()
    {
        $this->buscarCliente = '';
        $this->segmentoFiltro = '';
        $this->servicioFiltro = '';
        $this->estadoFiltro = '';
        $this->vencimientoFiltro = '';
        $this->clienteExpandidoId = null;
        $this->detalleExpandido = null;
        $this->resetPage();
    }

    /**
     * Aplica el filtro de estado desde las tarjetas de resumen.
     */
    public function filtrarPorEstado($estado)
    {
        $this->estadoFiltro = $estado === 'todos' ? '' : $estado;
        $this->resetPage();
        $this->cerrarDetalle();
    }

    /**
     * Aplica el filtro de vencimiento desde las tarjetas de resumen.
     */
    public function filtrarPorVencimiento($vencimiento)
    {
        $this->vencimientoFiltro = $vencimiento;
        $this->resetPage();
        $this->cerrarDetalle();
    }

    /**
     * Abre o cierra el detalle en línea de un cliente.
     */
    public function toggleDetalle($clienteId)
    {
        if ((int) $this->clienteExpandidoId === (int) $clienteId) {
            $this->cerrarDetalle();

            return;
        }

        $empresaId = Auth::user()->empresa_id;

        $cliente = Cliente::query()
            ->where('clientes.id', $clienteId)
            ->whereIn('clientes.id', DB::table('cliente_empresa')
                ->select('cliente_id')
                ->where('empresa_id', $empresaId))
            ->with([
                'segmentos',
                'servicios' => fn ($q) => $q->where('servicios.empresa_id', $empresaId),
            ])
            ->first();

        if (! $cliente) {
            session()->flash('error', 'El cliente no pertenece a esta empresa.');

            return;
        }

        $pagos = Pagos::query()
            ->join('servicio_pagar', 'servicio_pagar.id', '=', 'pagos.id_servicio_pagar')
            ->join('servicios', 'servicios.id', '=', 'servicio_pagar.servicio_id')
            ->where('servicio_pagar.cliente_id', $clienteId)
            ->where('servicios.empresa_id', $empresaId)
            ->orderByDesc('pagos.created_at')
            ->limit(5)
            ->get([
                'pagos.id',
                'pagos.importe',
                'pagos.importe2',
                'pagos.created_at',
                'servicios.nombre as servicio_nombre',
            ]);

        $registros = RegistroCerradura::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId);

        $this->detalleExpandido = [
            'cliente' => $cliente,
            'pagos' => $pagos,
            'accesos_semana' => (clone $registros)->where('fecha_hora', '>=', now()->startOfWeek())->count(),
            'ultimo_acceso' => (clone $registros)->max('fecha_hora'),
        ];

        $this->clienteExpandidoId = $cliente->id;
    }

    public function cerrarDetalle()
    {
        $this->clienteExpandidoId = null;
        $this->detalleExpandido = null;
    }

    /**
     * Iniciales del nombre para el avatar de la grilla.
     */
    public function iniciales($nombre): string
    {
        $partes = preg_split('/\s+/', trim((string) $nombre)) ?: [];
        $iniciales = mb_substr($partes[0] ?? '', 0, 1).mb_substr($partes[1] ?? '', 0, 1);

        return mb_strtoupper($iniciales !== '' ? $iniciales : '?');
    }

    /**
     * Abre el modal de confirmación para eliminar un cliente
     */
    public function confirmarEliminarCliente($clienteId)
    {
        $this->clienteAEliminar = Cliente::find($clienteId);
        $this->mostrarModalConfirmacion = true;
    }

    /**
     * Cancela la eliminación y cierra el modal
     */
    public function cancelarEliminacion()
    {
        $this->clienteAEliminar = null;
        $this->mostrarModalConfirmacion = false;
    }

    /**
     * Carga los registros de cerradura del cliente para la empresa actual
     * y abre el modal de visualización.
     */
    public function verEventosCerradura($clienteId)
    {
        $empresaId = Auth::user()->empresa_id;

        // Validar que el cliente pertenece a esta empresa
        $clientePerteneceAEmpresa = DB::table('cliente_empresa')
            ->where('cliente_id', $clienteId)
            ->where('empresa_id', $empresaId)
            ->exists();

        if (! $clientePerteneceAEmpresa) {
            session()->flash('error', 'El cliente no pertenece a esta empresa.');

            return;
        }

        $this->clienteEventos = Cliente::find($clienteId);
        $this->vistaEventos = 'calendario';
        $this->mesCalendario = now()->format('Y-m');
        $this->diaSeleccionado = null;

        $this->cargarCalendario();

        $this->mostrarModalEventos = true;
    }

    /**
     * Carga los registros del mes visible y arma la grilla del calendario.
     */
    protected function cargarCalendario(): void
    {
        if (! $this->clienteEventos || ! $this->mesCalendario) {
            return;
        }

        $empresaId = Auth::user()->empresa_id;
        $inicioMes = Carbon::createFromFormat('Y-m', $this->mesCalendario)->startOfMonth();
        $finMes = $inicioMes->copy()->endOfMonth();

        $registros = RegistroCerradura::where('empresa_id', $empresaId)
            ->where('cliente_id', $this->clienteEventos->id)
            ->whereBetween('fecha_hora', [$inicioMes, $finMes])
            ->orderBy('fecha_hora')
            ->get();

        $eventosMes = [];

        foreach ($registros as $registro) {
            $fecha = $registro->fecha_hora->format('Y-m-d');

            $eventosMes[$fecha][] = [
                'id' => $registro->id,
                'hora' => $registro->fecha_hora->format('H:i'),
                'fecha_hora' => $registro->fecha_hora->format('d/m/Y H:i:s'),
                'nombre_cerradura' => $registro->nombre_cerradura,
                'tipo' => $registro->tipo,
                'dispositivo' => $registro->dispositivo_id,
                'datos_extra' => $registro->datos_extra,
            ];
        }

        $this->eventosMes = $eventosMes;
        $this->eventosCerradura = $registros->sortByDesc('fecha_hora')->values();

        // Grilla de 6 semanas como máximo, de lunes a domingo
        $inicioGrilla = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $finGrilla = $finMes->copy()->endOfWeek(Carbon::SUNDAY);
        $hoy = now()->format('Y-m-d');
        $dias = [];

        for ($dia = $inicioGrilla->copy(); $dia->lte($finGrilla); $dia->addDay()) {
            $fecha = $dia->format('Y-m-d');

            $dias[] = [
                'fecha' => $fecha,
                'dia' => (int) $dia->format('j'),
                'del_mes' => (int) $dia->format('n') === (int) $inicioMes->format('n'),
                'hoy' => $fecha === $hoy,
                'cantidad' => count($eventosMes[$fecha] ?? []),
            ];
        }

        $this->diasCalendario = $dias;

        // Mantener el día seleccionado si pertenece al mes visible.
        $seleccionValida = $this->diaSeleccionado
            && str_starts_with($this->diaSeleccionado, $this->mesCalendario);

        if (! $seleccionValida) {
            if (str_starts_with($hoy, $this->mesCalendario)) {
                $this->diaSeleccionado = $hoy;
            } elseif ($eventosMes !== []) {
                $this->diaSeleccionado = array_key_first($eventosMes);
            } else {
                $this->diaSeleccionado = $inicioMes->format('Y-m-d');
            }
        }

        $this->eventosDia = $eventosMes[$this->diaSeleccionado] ?? [];
    }

    /**
     * Nombre del mes visible del calendario.
     */
    public function nombreMesCalendario(): string
    {
        if (! $this->mesCalendario) {
            return '';
        }

        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $fecha = Carbon::createFromFormat('Y-m', $this->mesCalendario);

        return $meses[(int) $fecha->format('n')].' '.$fecha->format('Y');
    }

    public function mesAnterior()
    {
        $this->cambiarMes(-1);
    }

    public function mesSiguiente()
    {
        $this->cambiarMes(1);
    }

    public function irMesActual()
    {
        $this->mesCalendario = now()->format('Y-m');
        $this->diaSeleccionado = null;
        $this->cargarCalendario();
    }

    protected function cambiarMes(int $delta): void
    {
        if (! $this->mesCalendario) {
            return;
        }

        $fecha = Carbon::createFromFormat('Y-m', $this->mesCalendario)->startOfMonth();

        $this->mesCalendario = $delta > 0
            ? $fecha->addMonthNoOverflow()->format('Y-m')
            : $fecha->subMonthNoOverflow()->format('Y-m');

        $this->diaSeleccionado = null;
        $this->cargarCalendario();
    }

    /**
     * Selecciona un día del mes visible y muestra sus accesos.
     */
    public function seleccionarDia($fecha)
    {
        if (! $this->mesCalendario || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $fecha)) {
            return;
        }

        [$anio, $mes, $dia] = array_map('intval', explode('-', $fecha));

        if (! checkdate($mes, $dia, $anio)) {
            return;
        }

        $fechaCarbon = Carbon::createFromFormat('Y-m-d', $fecha)->startOfDay();
        $inicioMes = Carbon::createFromFormat('Y-m', $this->mesCalendario)->startOfMonth();
        $finMes = $inicioMes->copy()->endOfMonth();

        if ($fechaCarbon->lt($inicioMes) || $fechaCarbon->gt($finMes)) {
            return;
        }

        $this->diaSeleccionado = $fechaCarbon->format('Y-m-d');
        $this->eventosDia = $this->eventosMes[$this->diaSeleccionado] ?? [];
    }

    /**
     * Alterna entre la vista de calendario y la de lista.
     */
    public function cambiarVista($vista)
    {
        if (in_array($vista, ['calendario', 'lista'], true)) {
            $this->vistaEventos = $vista;
        }
    }

    /**
     * Cierra el modal de registros de cerradura.
     */
    public function cerrarModalEventos()
    {
        $this->mostrarModalEventos = false;
        $this->clienteEventos = null;
        $this->eventosCerradura = [];
        $this->vistaEventos = 'calendario';
        $this->mesCalendario = null;
        $this->diaSeleccionado = null;
        $this->diasCalendario = [];
        $this->eventosMes = [];
        $this->eventosDia = [];
    }

    /**
     * Elimina el cliente de la empresa actual y todos sus servicios asociados
     * Solo elimina servicios, pagos y relaciones vinculadas a esta empresa
     * El cliente solo se elimina completamente si no pertenece a ninguna otra empresa
     */
    public function eliminarCliente()
    {
        if (! $this->clienteAEliminar) {
            session()->flash('error', 'No se pudo encontrar el cliente a eliminar.');

            return;
        }

        try {
            DB::beginTransaction();

            $clienteId = $this->clienteAEliminar->id;
            $clienteNombre = $this->clienteAEliminar->nombre;
            $empresaId = Auth::user()->empresa_id;
            $empresa = Empresa::find($empresaId);

            // Validar que el cliente pertenece a esta empresa
            $clientePerteneceAEmpresa = DB::table('cliente_empresa')
                ->where('cliente_id', $clienteId)
                ->where('empresa_id', $empresaId)
                ->exists();

            if (! $clientePerteneceAEmpresa) {
                DB::rollBack();
                session()->flash('error', 'El cliente no pertenece a esta empresa.');
                $this->mostrarModalConfirmacion = false;
                $this->clienteAEliminar = null;

                return;
            }

            // Obtener IDs de servicios de esta empresa (solo una vez)
            $serviciosIds = $empresa->servicios()->pluck('id')->toArray();

            // 1. Obtener todos los servicios a pagar del cliente en esta empresa
            $serviciosPagarIds = ServicioPagar::where('cliente_id', $clienteId)
                ->whereIn('servicio_id', $serviciosIds)
                ->pluck('id')
                ->toArray();

            // 2. Eliminar los pagos asociados a estos servicios
            if (! empty($serviciosPagarIds)) {
                Pagos::whereIn('id_servicio_pagar', $serviciosPagarIds)->delete();
            }

            // 3. Eliminar todos los registros de servicio_pagar de esta empresa
            ServicioPagar::where('cliente_id', $clienteId)
                ->whereIn('servicio_id', $serviciosIds)
                ->delete();

            // 4. Eliminar las relaciones cliente-servicio de esta empresa
            DB::table('cliente_servicio')
                ->where('cliente_id', $clienteId)
                ->whereIn('servicio_id', $serviciosIds)
                ->delete();

            // 5. Eliminar la relación cliente-empresa
            DB::table('cliente_empresa')
                ->where('cliente_id', $clienteId)
                ->where('empresa_id', $empresaId)
                ->delete();

            // 6. Verificar si el cliente pertenece a otras empresas
            $tieneOtrasEmpresas = DB::table('cliente_empresa')
                ->where('cliente_id', $clienteId)
                ->exists();

            // 7. Si no pertenece a ninguna otra empresa, eliminar el cliente completamente
            if (! $tieneOtrasEmpresas) {
                Cliente::destroy($clienteId);
            }

            DB::commit();

            // Cerrar el modal
            $this->mostrarModalConfirmacion = false;
            $this->clienteAEliminar = null;

            // Mensaje de éxito
            $mensajeEliminacion = $tieneOtrasEmpresas
                ? "Cliente '{$clienteNombre}' desvinculado de la empresa exitosamente."
                : "Cliente '{$clienteNombre}' eliminado completamente junto con todos sus servicios y pagos.";

            session()->flash('success', $mensajeEliminacion);

        } catch (\Exception $e) {
            DB::rollBack();

            // Cerrar el modal
            $this->mostrarModalConfirmacion = false;
            $this->clienteAEliminar = null;

            session()->flash('error', 'Error al eliminar el cliente: '.$e->getMessage());
        }
    }

    /**
     * Fecha de la próxima notificación automática configurada por la empresa.
     */
    protected function proximaNotificacion(?Empresa $empresa): ?Carbon
    {
        $dia = $empresa?->dia_notificacion;

        if (! $dia) {
            return null;
        }

        $hoy = now();
        $proxima = $hoy->copy()->day(min((int) $dia, $hoy->daysInMonth))->startOfDay();

        if ($proxima->isPast()) {
            $siguiente = $hoy->copy()->addMonthNoOverflow();
            $proxima = $siguiente->day(min((int) $dia, $siguiente->daysInMonth))->startOfDay();
        }

        return $proxima;
    }

    public function render()
    {
        $empresa = Empresa::find(Auth::user()->empresa_id);

        if (! $empresa) {
            abort(403, 'No se encontró la empresa del usuario.');
        }

        $empresaId = $empresa->id;

        $hoy = now()->startOfDay();
        $inicioSemana = now()->startOfWeek();
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();
        $enSieteDias = now()->addDays(7)->endOfDay();

        $segmentos = Segmento::where('empresa_id', $empresaId)->orderBy('nombre')->get();
        $servicios = Servicio::where('empresa_id', $empresaId)->orderBy('nombre')->get();

        /** Subconsulta de servicios impagos de clientes vinculados a la empresa. */
        $impagosEmpresa = fn () => DB::table('servicio_pagar as sp')
            ->join('servicios as s', 's.id', '=', 'sp.servicio_id')
            ->join('cliente_empresa as ce', 'ce.cliente_id', '=', 'sp.cliente_id')
            ->where('sp.estado', 'impago')
            ->where('s.empresa_id', $empresaId)
            ->where('ce.empresa_id', $empresaId);

        /** Subconsulta base: clientes vinculados a la empresa. */
        $clientesEmpresa = DB::table('cliente_empresa')
            ->select('cliente_id')
            ->where('empresa_id', $empresaId);

        /** Subconsulta de servicios que pertenecen a la empresa. */
        $serviciosEmpresaQuery = fn () => DB::table('servicios')
            ->select('id')
            ->where('empresa_id', $empresaId);

        /** Subconsulta de servicios impagos del cliente. */
        $impagosCliente = fn () => DB::table('servicio_pagar as sp_imp')
            ->join('servicios as s_imp', 's_imp.id', '=', 'sp_imp.servicio_id')
            ->whereColumn('sp_imp.cliente_id', 'clientes.id')
            ->where('s_imp.empresa_id', $empresaId)
            ->where('sp_imp.estado', 'impago');

        $query = Cliente::query()
            ->whereIn('clientes.id', $clientesEmpresa)
            ->select('clientes.*')
            ->with([
                'segmentos',
                'servicios' => fn ($q) => $q->where('servicios.empresa_id', $empresaId),
            ])
            ->addSelect([
                'impagos_count' => $impagosCliente()->selectRaw('count(*)'),
                'impago_vencido_desde' => $impagosCliente()
                    ->where('sp_imp.fecha_vencimiento', '<', $hoy)
                    ->selectRaw('min(sp_imp.fecha_vencimiento)'),
                'impago_proximo' => $impagosCliente()
                    ->where('sp_imp.fecha_vencimiento', '>=', $hoy)
                    ->selectRaw('min(sp_imp.fecha_vencimiento)'),
                'accesos_semana' => DB::table('registros_cerradura as rc')
                    ->whereColumn('rc.cliente_id', 'clientes.id')
                    ->where('rc.empresa_id', $empresaId)
                    ->where('rc.fecha_hora', '>=', $inicioSemana)
                    ->selectRaw('count(*)'),
                'ultimo_acceso' => DB::table('registros_cerradura as rc')
                    ->whereColumn('rc.cliente_id', 'clientes.id')
                    ->where('rc.empresa_id', $empresaId)
                    ->selectRaw('max(rc.fecha_hora)'),
            ]);

        // Búsqueda libre
        if ($this->buscarCliente) {
            $texto = trim($this->buscarCliente);
            $query->where(function ($q) use ($texto) {
                $q->where('clientes.nombre', 'like', '%'.$texto.'%')
                    ->orWhere('clientes.titular', 'like', '%'.$texto.'%')
                    ->orWhere('clientes.correo', 'like', '%'.$texto.'%')
                    ->orWhere('clientes.telefono', 'like', '%'.$texto.'%')
                    ->orWhere('clientes.dni', 'like', '%'.$texto.'%')
                    ->orWhere('clientes.domicilio', 'like', '%'.$texto.'%');
            });
        }

        // Filtro por segmento
        if ($this->segmentoFiltro) {
            $query->whereHas('segmentos', function ($q) {
                $q->where('segmentos.id', $this->segmentoFiltro);
            });
        }

        // Filtro por servicio
        if ($this->servicioFiltro) {
            $query->whereHas('servicios', function ($q) use ($empresaId) {
                $q->where('servicios.id', $this->servicioFiltro)
                    ->where('servicios.empresa_id', $empresaId);
            });
        }

        // Filtro con/sin servicio activo
        if ($this->estadoFiltro === 'con') {
            $query->whereHas('servicios', function ($q) use ($empresaId) {
                $q->where('servicios.empresa_id', $empresaId)
                    ->where('servicios.activo', true);
            });
        } elseif ($this->estadoFiltro === 'sin') {
            $query->whereDoesntHave('servicios', function ($q) use ($empresaId) {
                $q->where('servicios.empresa_id', $empresaId)
                    ->where('servicios.activo', true);
            });
        }

        // Filtro por vencimiento de servicios impagos
        if ($this->vencimientoFiltro === 'vencido') {
            $query->whereHas('serviciosPagar', function ($q) use ($serviciosEmpresaQuery, $hoy) {
                $q->where('servicio_pagar.estado', 'impago')
                    ->whereIn('servicio_pagar.servicio_id', $serviciosEmpresaQuery())
                    ->where('servicio_pagar.fecha_vencimiento', '<', $hoy);
            });
        } elseif ($this->vencimientoFiltro === 'mes') {
            $query->whereHas('serviciosPagar', function ($q) use ($serviciosEmpresaQuery, $inicioMes, $finMes) {
                $q->where('servicio_pagar.estado', 'impago')
                    ->whereIn('servicio_pagar.servicio_id', $serviciosEmpresaQuery())
                    ->whereBetween('servicio_pagar.fecha_vencimiento', [$inicioMes, $finMes]);
            });
        } elseif ($this->vencimientoFiltro === 'semana') {
            $query->whereHas('serviciosPagar', function ($q) use ($serviciosEmpresaQuery, $hoy, $enSieteDias) {
                $q->where('servicio_pagar.estado', 'impago')
                    ->whereIn('servicio_pagar.servicio_id', $serviciosEmpresaQuery())
                    ->whereBetween('servicio_pagar.fecha_vencimiento', [$hoy, $enSieteDias]);
            });
        }

        $clientes = $query->orderBy('clientes.nombre')->paginate(15);

        // Datos derivados por fila (estado, vencimiento y servicio principal)
        $clientes->getCollection()->transform(function (Cliente $cliente) use ($hoy) {
            $servicios = $cliente->servicios
                ->sortBy(fn ($servicio) => optional($servicio->pivot->vencimiento)->timestamp ?? PHP_INT_MAX)
                ->values();

            $vencimientosRenovacion = $servicios
                ->map(fn ($servicio) => $servicio->pivot->vencimiento
                    ? Carbon::parse($servicio->pivot->vencimiento)->startOfDay()
                    : null)
                ->filter()
                ->sort()
                ->values();

            $impagoVencido = $cliente->impago_vencido_desde
                ? Carbon::parse($cliente->impago_vencido_desde)->startOfDay()
                : null;

            $impagoProximo = $cliente->impago_proximo
                ? Carbon::parse($cliente->impago_proximo)->startOfDay()
                : null;

            $tieneImpagos = (int) $cliente->impagos_count > 0;
            $tieneActivo = $servicios->contains(fn ($servicio) => (bool) $servicio->activo);

            $vencimientoCercano = $impagoVencido
                ?? $impagoProximo
                ?? $vencimientosRenovacion->first();

            $impagoPorVencer = $impagoProximo
                && $impagoProximo->between($hoy, $hoy->copy()->addDays(7));

            if ($impagoVencido) {
                $estado = ['label' => 'Vencido', 'clase' => 'vencido'];
            } elseif ($impagoPorVencer) {
                $estado = ['label' => 'Por vencer', 'clase' => 'por-vencer'];
            } elseif ($servicios->isEmpty()) {
                $estado = ['label' => 'Sin servicio', 'clase' => 'sin-servicio'];
            } elseif (! $tieneActivo && ! $tieneImpagos) {
                $estado = ['label' => 'Sin servicio', 'clase' => 'sin-servicio'];
            } else {
                $estado = ['label' => 'Activo', 'clase' => 'activo'];
            }

            $cliente->setAttribute('servicios_ordenados', $servicios);
            $cliente->setAttribute('servicio_principal', $servicios->first());
            $cliente->setAttribute('vencimiento_cercano', $vencimientoCercano);
            $cliente->setAttribute('vencimiento_es_impago', $impagoVencido !== null || $impagoProximo !== null);
            $cliente->setAttribute('estado_label', $estado['label']);
            $cliente->setAttribute('estado_clase', $estado['clase']);
            $cliente->setAttribute('tiene_servicio_activo', $tieneActivo);

            return $cliente;
        });

        // Indicadores de resumen
        $totalClientes = $clientesEmpresa->count();

        $clientesConServicioActivo = DB::table('cliente_empresa as ce')
            ->join('cliente_servicio as cs', 'cs.cliente_id', '=', 'ce.cliente_id')
            ->join('servicios as s', 's.id', '=', 'cs.servicio_id')
            ->where('ce.empresa_id', $empresaId)
            ->where('s.empresa_id', $empresaId)
            ->where('s.activo', true)
            ->distinct()
            ->count('ce.cliente_id');

        $clientesVencenEsteMes = $impagosEmpresa()
            ->whereBetween('sp.fecha_vencimiento', [$inicioMes, $finMes])
            ->distinct()
            ->count('sp.cliente_id');

        $impagosPorVencer = $impagosEmpresa()
            ->whereBetween('sp.fecha_vencimiento', [$hoy, $enSieteDias])
            ->distinct()
            ->count('sp.id');

        $impagosVencidos = $impagosEmpresa()
            ->where('sp.fecha_vencimiento', '<', $hoy)
            ->distinct()
            ->count('sp.id');

        $accesosSemana = RegistroCerradura::where('empresa_id', $empresaId)
            ->where('fecha_hora', '>=', $inicioSemana)
            ->count();

        $resumen = [
            'total' => $totalClientes,
            'con_servicio' => $clientesConServicioActivo,
            'sin_servicio' => max($totalClientes - $clientesConServicioActivo, 0),
            'vencen_mes' => $clientesVencenEsteMes,
            'segmentos' => $segmentos->count(),
            'accesos_semana' => $accesosSemana,
        ];

        return view('livewire.ver-cliente.ver-cliente', [
            'clientes' => $clientes,
            'segmentos' => $segmentos,
            'servicios' => $servicios,
            'resumen' => $resumen,
            'proximaNotificacion' => $this->proximaNotificacion($empresa),
            'serviciosPorVencer' => $impagosPorVencer,
            'impagosVencidos' => $impagosVencidos,
        ])
            ->extends('principal.principal')
            ->section('body');
    }
}
