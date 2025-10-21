<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\ServicioPagar;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    /**
     * Buscar clientes por nombre, correo, telefono o dni.
     * Parámetros:
     * - q: término de búsqueda (requerido)
     * - per_page: (opcional) resultados por página, default 15
     * - page: (opcional) página, default 1
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1'
        ]);

        $q = $request->input('q');
        $perPage = (int) $request->input('per_page', 15);

        $query = Cliente::query();

        // Si hay usuario autenticado y tiene empresa_id, limitamos la búsqueda a sus clientes vinculados
        $user = Auth::user();
        if ($user && isset($user->empresa_id)) {
            // Hay una tabla intermedia cliente_empresa en el proyecto; hacemos join
            $query = $query->select('clientes.*')
                ->join('cliente_empresa', 'cliente_empresa.cliente_id', '=', 'clientes.id')
                ->where('cliente_empresa.empresa_id', $user->empresa_id);
        }

        $query->where(function($qBuilder) use ($q) {
            $like = '%' . $q . '%';
            $qBuilder->where('clientes.nombre', 'like', $like)
                ->orWhere('clientes.correo', 'like', $like)
                ->orWhere('clientes.telefono', 'like', $like)
                ->orWhere('clientes.dni', 'like', $like);
        });

        // Obtener todos los clientes que coinciden (sin paginación)
        $clientesCollection = $query->orderBy('clientes.id', 'desc')->get();

        if ($clientesCollection->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $clienteIds = $clientesCollection->pluck('id')->all();

        // Eager load servicios
        $clientes = Cliente::whereIn('id', $clienteIds)
            ->with('servicios')
            ->get()
            ->keyBy('id');

            
        foreach ($clientesCollection as $clienteItem) {
                
                // Cargar todos los registros de servicio_pagar para estos clientes
                $serviciosPagos = ServicioPagar::where('cliente_id', $clienteItem->id)
                    ->where('estado', 'pago')->limit(50)->get();

                $serviciosImpagos = ServicioPagar::where('cliente_id', $clienteItem->id)
                    ->where('estado', 'impago')->get();

                // Agregar a la colección clientesCollection los servicios pagos e impagos
                $clienteItem->servicios_pagos = $serviciosPagos;
                $clienteItem->servicios_impagos = $serviciosImpagos;

                // Determinar estado_cliente
                $clienteItem->estado_cliente = $serviciosImpagos->isEmpty() ? true : false;


        }

        return response()->json(['data' => $clientesCollection->all()]);

    }
}
