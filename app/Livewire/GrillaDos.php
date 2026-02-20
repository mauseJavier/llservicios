<?php

namespace App\Livewire;


use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Models\Cliente;
use App\Models\Servicio;

class GrillaDos extends Component
{
    public $buscar = '';
    public $year;


    // Paginación eliminada

    public function mount()
    {
        $this->buscar = '';
        $this->year = date('Y'); // Año actual por defecto
    }

    public function getClientes()
    {
        $usuario = Auth::user();
        $buscar = $this->buscar;

        if ($buscar) {
            $clientes = DB::select('SELECT b.* FROM cliente_empresa a, clientes b WHERE a.cliente_id = b.id and a.empresa_id = ? and (b.nombre like ? or b.dni like ? or b.titular like ?)', [$usuario->empresa_id, "%" . $buscar . "%", "%" . $buscar . "%", "%" . $buscar . "%"]);
        } else {
            $clientes = DB::select('SELECT b.* FROM cliente_empresa a, clientes b WHERE a.cliente_id = b.id and a.empresa_id = ?', [$usuario->empresa_id]);
        }

        // Generar todos los periodos del año seleccionado (enero a diciembre)
        $periodos_completos = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $periodos_completos[] = sprintf('%04d-%02d', $this->year, $mes);
        }

        foreach ($clientes as $clave => $valor) {
            $servicios = Servicio::where('empresa_id', $usuario->empresa_id)
                ->join('servicio_pagar', 'servicios.id', '=', 'servicio_pagar.servicio_id')
                ->select(
                    DB::raw("DATE_FORMAT(COALESCE(servicio_pagar.periodo_servicio, servicio_pagar.created_at), '%Y-%m') AS periodo"),
                    'servicio_pagar.estado as estado_pago',
                    DB::raw("SUM(servicio_pagar.precio * servicio_pagar.cantidad) as suma_precios")
                )
                ->where('servicio_pagar.cliente_id', $valor->id)
                ->whereYear(DB::raw('COALESCE(servicio_pagar.periodo_servicio, servicio_pagar.created_at)'), $this->year)
                ->groupBy('periodo', 'estado_pago')
                ->orderBy(DB::raw('COALESCE(servicio_pagar.periodo_servicio, servicio_pagar.created_at)'), 'ASC')
                ->get();

            $datos_completos = [];
            foreach ($periodos_completos as $periodo) {
                $datos_completos[] = [
                    'periodo' => $periodo,
                    'importe_pagado' => 0,
                    'importe_impago' => 0
                ];
            }

            foreach ($datos_completos as $index => $dc) {
                foreach ($servicios as $servicio) {
                    if ($servicio->periodo === $dc['periodo']) {
                        if ($servicio->estado_pago === 'pago') {
                            $datos_completos[$index]['importe_pagado'] += $servicio->suma_precios;
                        } elseif ($servicio->estado_pago === 'impago') {
                            $datos_completos[$index]['importe_impago'] += $servicio->suma_precios;
                        }
                    }
                }
            }
            $clientes[$clave]->datos = $datos_completos;
        }


        $total = [];
        foreach ($periodos_completos as $periodo) {
            $total[] = [
                "mes" => $periodo,
                "pago" => 0,
                "impago" => 0,
                "total" => 0,
            ];
        }

        foreach ($clientes as $cliente) {
            if (!isset($cliente->datos)) continue;
            foreach ($cliente->datos as $index => $dato) {
                $total[$index]['pago'] += $dato['importe_pagado'];
                $total[$index]['impago'] += $dato['importe_impago'];
            }
        }
        foreach ($total as $index => $mes) {
            $total[$index]['total'] = $mes['pago'] - $mes['impago'];
        }

        // Sin paginación, se retorna todo
        return [$clientes, $total];
    }

    public function render()
    {
        list($clientes, $total) = $this->getClientes();

        return view('livewire.grilla-dos', [
            'clientes' => $clientes,
            'total' => $total,
            'buscar' => $this->buscar,
            'year' => $this->year
        ])
        ->extends('principal.principal')
        ->section('body'); 
    }
}
