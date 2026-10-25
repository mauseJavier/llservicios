<?php

namespace App\Livewire;

use App\Models\Empresa;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MenuPrincipal extends Component
{
    public bool $abierto = false;

    public string $busqueda = '';

    public function abrir(): void
    {
        $this->abierto = true;
        $this->busqueda = '';

        $this->dispatch('menu-principal-abierto');
    }

    public function cerrar(): void
    {
        if (! $this->abierto) {
            return;
        }

        $this->abierto = false;
        $this->busqueda = '';

        $this->dispatch('menu-principal-cerrado');
    }

    public function limpiarBusqueda(): void
    {
        $this->busqueda = '';
    }

    #[Computed]
    public function empresa(): ?Empresa
    {
        return Auth::user()?->empresa;
    }

    #[Computed]
    public function whatsappState(): ?string
    {
        $empresa = $this->empresa;

        if (! $empresa || ! $empresa->instanciaWS || ! $empresa->tokenWS) {
            return null;
        }

        return Cache::remember(
            "whatsapp_state_{$empresa->id}",
            30,
            function () use ($empresa) {
                $service = new WhatsAppService($empresa->instanciaWS, $empresa->tokenWS);
                $resultado = $service->getConnectionState($empresa->instanciaWS);

                return $resultado['state'] ?? null;
            }
        );
    }

    #[Computed]
    public function categorias(): array
    {
        $rol = Auth::user()?->role?->nombre;

        $categorias = [
            [
                'titulo' => 'Operación',
                'color' => '#2563eb',
                'accesos' => [
                    ['label' => 'Panel', 'descripcion' => 'Resumen general', 'ruta' => 'panelServicios', 'icono' => 'fas fa-gauge-high'],
                    ['label' => 'Agregar Servicio', 'descripcion' => 'Nuevo cobro', 'ruta' => 'NuevoCobro', 'icono' => 'fas fa-cart-plus'],
                ],
            ],
            [
                'titulo' => 'Clientes',
                'color' => '#7c3aed',
                'accesos' => [
                    ['label' => 'Clientes', 'descripcion' => 'Padrón de clientes', 'ruta' => 'Cliente.index', 'icono' => 'fas fa-users', 'roles' => ['Super', 'Admin']],
                    ['label' => 'Segmentos', 'descripcion' => 'Agrupación de clientes', 'ruta' => 'segmentos', 'icono' => 'fas fa-tags', 'roles' => ['Super', 'Admin']],
                    ['label' => 'Grilla Clientes', 'descripcion' => 'Vista rápida por estados', 'ruta' => 'Grilla', 'icono' => 'fas fa-table-cells', 'roles' => ['Super', 'Admin']],
                    ['label' => 'Servicios', 'descripcion' => 'Catálogo y precios', 'ruta' => 'Servicios.index', 'icono' => 'fas fa-boxes-stacked', 'roles' => ['Super', 'Admin']],
                ],
            ],
            [
                'titulo' => 'Cobros',
                'color' => '#059669',
                'accesos' => [
                    [
                        'label' => 'Pagos',
                        'descripcion' => 'Cobros del día',
                        'ruta' => 'Pagos',
                        'parametros' => ['fecha_inicio' => now()->format('Y-m-d'), 'fecha_fin' => now()->format('Y-m-d')],
                        'icono' => 'fas fa-money-bill-wave',
                        'roles' => ['Super', 'Admin'],
                    ],
                    ['label' => 'Impagos', 'descripcion' => 'Deudas pendientes', 'ruta' => 'ServiciosImpagos', 'icono' => 'fas fa-triangle-exclamation', 'roles' => ['Super', 'Admin']],
                    ['label' => 'Gastos', 'descripcion' => 'Egresos registrados', 'ruta' => 'expenses.index', 'icono' => 'fas fa-receipt', 'roles' => ['Super', 'Admin']],
                    ['label' => 'Cierre de Caja', 'descripcion' => 'Cierre del día', 'ruta' => 'cierre-caja', 'icono' => 'fas fa-cash-register', 'roles' => ['Super', 'Admin']],
                    ['label' => 'Historial de Caja', 'descripcion' => 'Cierres anteriores', 'ruta' => 'cierre-caja.historial', 'icono' => 'fas fa-clock-rotate-left', 'roles' => ['Super', 'Admin']],
                ],
            ],
            [
                'titulo' => 'Mercado Pago',
                'color' => '#0ea5e9',
                'accesos' => [
                    ['label' => 'Gestión QR MP', 'descripcion' => 'Cajas y códigos QR', 'ruta' => 'mercadopago.qr-manager', 'icono' => 'fas fa-qrcode', 'roles' => ['Super', 'Admin']],
                    ['label' => 'Reembolsos MP', 'descripcion' => 'Devoluciones de pagos', 'ruta' => 'mercadopago.reembolsos', 'icono' => 'fas fa-rotate-left', 'roles' => ['Super', 'Admin']],
                ],
            ],
            [
                'titulo' => 'Administración',
                'color' => '#d97706',
                'accesos' => [
                    ['label' => 'Usuarios', 'descripcion' => 'Altas y permisos', 'ruta' => 'usuarios', 'icono' => 'fas fa-user-gear', 'roles' => ['Super']],
                    ['label' => 'Empresas', 'descripcion' => 'Configuración de empresas', 'ruta' => 'empresas.index', 'icono' => 'fas fa-building', 'roles' => ['Super']],
                    ['label' => 'Arca', 'descripcion' => 'Certificados AFIP', 'ruta' => 'afip.certificados', 'icono' => 'fas fa-file-shield', 'roles' => ['Super']],
                    ['label' => 'Ver Logs', 'descripcion' => 'Actividad del sistema', 'ruta' => 'logs.index', 'icono' => 'fas fa-list-ul', 'roles' => ['Super']],
                    ['label' => 'Auditoría MP', 'descripcion' => 'Pagos y eventos', 'ruta' => 'mercadopago.auditoria', 'icono' => 'fas fa-magnifying-glass-chart', 'roles' => ['Super']],
                ],
            ],
        ];

        $cuenta = [
            ['label' => 'Mi perfil', 'descripcion' => 'Datos y contraseña', 'ruta' => 'miPerfil', 'icono' => 'fas fa-user-gear'],
        ];

        if ($this->whatsappState !== null) {
            $cuenta[] = ['label' => 'WhatsApp', 'descripcion' => 'Estado de la conexión', 'ruta' => 'whatsapp.admin', 'icono' => 'fab fa-whatsapp', 'color' => '#22c55e'];
        }

        $cuenta[] = ['label' => 'Salir', 'descripcion' => 'Cerrar sesión', 'ruta' => 'logout', 'icono' => 'fas fa-right-from-bracket', 'color' => '#dc2626', 'peligro' => true];

        $categorias[] = ['titulo' => 'Cuenta', 'color' => '#64748b', 'accesos' => $cuenta];

        $categorias = collect($categorias)
            ->map(function (array $categoria) use ($rol) {
                $categoria['accesos'] = array_values(array_filter(
                    $categoria['accesos'],
                    fn (array $acceso) => empty($acceso['roles']) || in_array($rol, $acceso['roles'], true)
                ));

                return $categoria;
            })
            ->filter(fn (array $categoria) => ! empty($categoria['accesos']));

        $termino = $this->normalizar($this->busqueda);

        if ($termino !== '') {
            $categorias = $categorias
                ->map(function (array $categoria) use ($termino) {
                    $coincideCategoria = str_contains($this->normalizar($categoria['titulo']), $termino);

                    if ($coincideCategoria) {
                        return $categoria;
                    }

                    $categoria['accesos'] = array_values(array_filter(
                        $categoria['accesos'],
                        fn (array $acceso) => str_contains(
                            $this->normalizar($acceso['label'].' '.($acceso['descripcion'] ?? '')),
                            $termino
                        )
                    ));

                    return $categoria;
                })
                ->filter(fn (array $categoria) => ! empty($categoria['accesos']));
        }

        return $categorias->values()->all();
    }

    private function normalizar(?string $texto): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower(Str::ascii((string) $texto))));
    }

    public function render()
    {
        return view('livewire.menu-principal', [
            'categorias' => $this->categorias,
            'empresa' => $this->empresa,
            'whatsappState' => $this->whatsappState,
        ]);
    }
}
