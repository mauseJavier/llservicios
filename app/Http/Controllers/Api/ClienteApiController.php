<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClienteApiController extends Controller
{
    /**
     * Buscar cliente por DNI, correo o nombre
     * Devuelve los servicios pagos e impagos del cliente
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function buscarCliente(Request $request): JsonResponse
    {
        try {
            // Validar que al menos uno de los parámetros de búsqueda esté presente
            $request->validate([
                'dni' => 'nullable|string',
                'correo' => 'nullable|email',
                'nombre' => 'nullable|string',
                'empresa_id' => 'nullable|integer',
                'nombre_empresa' => 'nullable|string',
            ]);

            $dni = $request->input('dni');
            $correo = $request->input('correo');
            $nombre = $request->input('nombre');
            $empresaId = $request->input('empresa_id');
            $nombreEmpresa = $request->input('nombre_empresa');

            // Verificar que al menos un parámetro de búsqueda del cliente esté presente
            if (!$dni && !$correo && !$nombre) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar al menos uno de los siguientes parámetros: dni, correo o nombre'
                ], 400);
            }

            // Buscar el cliente
            $query = Cliente::query();

            if ($dni) {
                $query->where('dni', $dni);
            }

            if ($correo) {
                $query->orWhere('correo', 'LIKE', "%{$correo}%");
            }

            if ($nombre) {
                $query->orWhere('nombre', 'LIKE', "%{$nombre}%");
            }

            $cliente = $query->first();

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            // Construir la consulta para servicios pagos e impagos
            $serviciosPagosQuery = $cliente->serviciosPagos()->with(['servicio.empresa']);
            $serviciosImpagosQuery = $cliente->serviciosImpagos()->with(['servicio.empresa']);

            // Aplicar filtros de empresa si están presentes
            if ($empresaId || $nombreEmpresa) {
                $serviciosPagosQuery->whereHas('servicio.empresa', function ($q) use ($empresaId, $nombreEmpresa) {
                    if ($empresaId) {
                        $q->where('id', $empresaId);
                    }
                    if ($nombreEmpresa) {
                        $q->where('nombre', 'LIKE', "%{$nombreEmpresa}%");
                    }
                });

                $serviciosImpagosQuery->whereHas('servicio.empresa', function ($q) use ($empresaId, $nombreEmpresa) {
                    if ($empresaId) {
                        $q->where('id', $empresaId);
                    }
                    if ($nombreEmpresa) {
                        $q->where('nombre', 'LIKE', "%{$nombreEmpresa}%");
                    }
                });
            }

            // Obtener los servicios
            $serviciosPagos = $serviciosPagosQuery->get()->map(function ($servicioPagar) {
                return [
                    'id' => $servicioPagar->id,
                    'servicio_id' => $servicioPagar->servicio_id,
                    'servicio_nombre' => $servicioPagar->servicio->nombre ?? null,
                    'empresa_id' => $servicioPagar->servicio->empresa->id ?? null,
                    'empresa_nombre' => $servicioPagar->servicio->empresa->nombre ?? null,
                    'cantidad' => $servicioPagar->cantidad,
                    'precio' => $servicioPagar->precio,
                    'total' => $servicioPagar->total,
                    'estado' => $servicioPagar->estado,
                    'fecha_vencimiento' => $servicioPagar->fecha_vencimiento,
                    'periodo_servicio' => $servicioPagar->periodo_servicio,
                    'mp_payment_id' => $servicioPagar->mp_payment_id,
                    'comentario' => $servicioPagar->comentario,
                    'created_at' => $servicioPagar->created_at,
                    'updated_at' => $servicioPagar->updated_at,
                ];
            });

            $serviciosImpagos = $serviciosImpagosQuery->get()->map(function ($servicioPagar) {
                return [
                    'id' => $servicioPagar->id,
                    'servicio_id' => $servicioPagar->servicio_id,
                    'servicio_nombre' => $servicioPagar->servicio->nombre ?? null,
                    'empresa_id' => $servicioPagar->servicio->empresa->id ?? null,
                    'empresa_nombre' => $servicioPagar->servicio->empresa->nombre ?? null,
                    'cantidad' => $servicioPagar->cantidad,
                    'precio' => $servicioPagar->precio,
                    'total' => $servicioPagar->total,
                    'estado' => $servicioPagar->estado,
                    'fecha_vencimiento' => $servicioPagar->fecha_vencimiento,
                    'periodo_servicio' => $servicioPagar->periodo_servicio,
                    'mp_preference_id' => $servicioPagar->mp_preference_id,
                    'comentario' => $servicioPagar->comentario,
                    'created_at' => $servicioPagar->created_at,
                    'updated_at' => $servicioPagar->updated_at,
                ];
            });

            // Determinar el estado del cliente
            $cantidadImpagos = $serviciosImpagos->count();
            $estadoCliente = $cantidadImpagos > 0 ? false : true;

            // Construir la respuesta
            $response = [
                'success' => true,
                'data' => [
                    'cliente' => [
                        'id' => $cliente->id,
                        'nombre' => $cliente->nombre,
                        'dni' => $cliente->dni,
                        'correo' => $cliente->correo,
                        'telefono' => $cliente->telefono ?? null,
                        'direccion' => $cliente->direccion ?? null,
                    ],
                    'estado_cliente' => $estadoCliente,
                    'servicios_pagos' => $serviciosPagos,
                    'servicios_impagos' => $serviciosImpagos,
                    'resumen' => [
                        'total_pagos' => $serviciosPagos->count(),
                        'total_impagos' => $cantidadImpagos,
                        'monto_total_impagos' => $serviciosImpagos->sum('total'),
                    ]
                ]
            ];

            return response()->json($response, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
