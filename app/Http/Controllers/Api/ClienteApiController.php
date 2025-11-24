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

            // Verificar si los datos de empresa están presentes
            if ($empresaId || $nombreEmpresa) {
                // Verificar si el cliente está vinculado a la empresa
                $estaVinculado = $cliente->empresas()->where(function ($q) use ($empresaId, $nombreEmpresa) {
                    if ($empresaId) {
                        $q->where('empresas.id', $empresaId);
                    }
                    if ($nombreEmpresa) {
                        $q->where('empresas.nombre', 'LIKE', "%{$nombreEmpresa}%");
                    }
                })->exists();

                if (!$estaVinculado) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El cliente no está vinculado a la empresa especificada'
                    ], 403);
                }
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

    /**
     * Guardar un nuevo cliente y vincularlo a una empresa
     * Si el cliente ya existe (mismo nombre), devuelve el cliente existente
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function guardarCliente(Request $request): JsonResponse
    {
        try {
            // Validar los datos del cliente
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'correo' => 'nullable|email|max:255',
                'telefono' => 'nullable|string|max:255',
                'dni' => 'nullable|integer',
                'domicilio' => 'nullable|string|max:255',
                'empresa_id' => 'required|integer|exists:empresas,id',
            ]);

            // Verificar si ya existe un cliente con el mismo nombre
            $clienteExistente = Cliente::where('dni', $validated['dni'])->first();

            if ($clienteExistente) {
                // Cliente ya existe, verificar si ya está vinculado a la empresa
                $yaVinculado = $clienteExistente->empresas()->where('empresa_id', $validated['empresa_id'])->exists();

                if (!$yaVinculado) {
                    // Vincular el cliente existente con la empresa
                    $clienteExistente->empresas()->attach($validated['empresa_id']);
                }

                // Cargar la relación de empresas
                $clienteExistente->load('empresas');

                return response()->json([
                    'success' => true,
                    'message' => 'Cliente ya existente. Se vinculó a la empresa solicitada.',
                    'cliente_existente' => true,
                    'data' => [
                        'cliente' => [
                            'id' => $clienteExistente->id,
                            'nombre' => $clienteExistente->nombre,
                            'correo' => $clienteExistente->correo,
                            'telefono' => $clienteExistente->telefono,
                            'dni' => $clienteExistente->dni,
                            'domicilio' => $clienteExistente->domicilio,
                            'created_at' => $clienteExistente->created_at,
                            'updated_at' => $clienteExistente->updated_at,
                        ],
                        'empresas' => $clienteExistente->empresas->map(function ($empresa) {
                            return [
                                'id' => $empresa->id,
                                'nombre' => $empresa->nombre,
                            ];
                        })
                    ]
                ], 200);
            }

            // Validar DNI único solo si se proporciona y no existe el cliente
            if (isset($validated['dni'])) {
                $dniExistente = Cliente::where('dni', $validated['dni'])->first();
                if ($dniExistente) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El DNI ya está registrado para otro cliente',
                        'errors' => [
                            'dni' => ['El DNI ya ha sido registrado.']
                        ]
                    ], 422);
                }
            }

            // Crear el cliente
            $cliente = Cliente::create([
                'nombre' => $validated['nombre'],
                'correo' => $validated['correo'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'dni' => $validated['dni'] ?? null,
                'domicilio' => $validated['domicilio'] ?? null,
            ]);

            // Vincular el cliente con la empresa
            $cliente->empresas()->attach($validated['empresa_id']);

            // Cargar la relación de empresas para devolverla en la respuesta
            $cliente->load('empresas');

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado y vinculado exitosamente',
                'cliente_existente' => false,
                'data' => [
                    'cliente' => [
                        'id' => $cliente->id,
                        'nombre' => $cliente->nombre,
                        'correo' => $cliente->correo,
                        'telefono' => $cliente->telefono,
                        'dni' => $cliente->dni,
                        'domicilio' => $cliente->domicilio,
                        'created_at' => $cliente->created_at,
                        'updated_at' => $cliente->updated_at,
                    ],
                    'empresas' => $cliente->empresas->map(function ($empresa) {
                        return [
                            'id' => $empresa->id,
                            'nombre' => $empresa->nombre,
                        ];
                    })
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
