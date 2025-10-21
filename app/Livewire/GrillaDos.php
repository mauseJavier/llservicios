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


    // Paginación eliminada

    public function mount()
    {
        $this->buscar = '';
    }

    public function getClientes()
    {
        $usuario = Auth::user();
        $buscar = $this->buscar;

        if ($buscar) {
            $clientes = DB::select('SELECT b.* FROM cliente_empresa a, clientes b WHERE a.cliente_id = b.id and a.empresa_id = ? and (b.nombre like ? or b.dni like ?)', [$usuario->empresa_id, "%" . $buscar . "%", "%" . $buscar . "%"]);
        } else {
            $clientes = DB::select('SELECT b.* FROM cliente_empresa a, clientes b WHERE a.cliente_id = b.id and a.empresa_id = ?', [$usuario->empresa_id]);
        }

        $meses_completos = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

        foreach ($clientes as $clave => $valor) {
            $servicios = Servicio::where('empresa_id', $usuario->empresa_id)
                ->join('servicio_pagar', 'servicios.id', '=', 'servicio_pagar.servicio_id')
                ->select(
                    DB::raw("MONTHNAME(servicio_pagar.created_at) AS mes_creado"),
                    'servicio_pagar.estado as estado_pago',
                    DB::raw("SUM(servicio_pagar.precio * servicio_pagar.cantidad) as suma_precios")
                )
                ->where('servicio_pagar.cliente_id', $valor->id)
                ->groupBy('mes_creado', 'estado_pago')
                ->orderBy('servicio_pagar.created_at', 'ASC')
                ->get();

            $datos_completos = [];
            foreach ($meses_completos as $mes) {
                $datos_completos[] = [
                    'mes_creado' => $mes,
                    'importe_pagado' => 0,
                    'importe_impago' => 0
                ];
            }

            foreach ($datos_completos as $index => $dc) {
                foreach ($servicios as $servicio) {
                    if ($servicio->mes_creado === $dc['mes_creado']) {
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
        foreach ($meses_completos as $mes) {
            $total[] = [
                "mes" => $mes,
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
            'buscar' => $this->buscar
        ])
        ->extends('principal.principal')
        ->section('body'); 
    }
}
