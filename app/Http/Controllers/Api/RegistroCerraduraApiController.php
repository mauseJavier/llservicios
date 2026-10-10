<?php

namespace App\Http\Controllers\Api;

use App\Helpers\DniHelper;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\RegistroCerradura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RegistroCerraduraApiController extends Controller
{
    /**
     * Registrar un evento de cerradura enviado por el DLL de la empresa.
     * La empresa se autentica mediante el token global (middleware empresa.token).
     *
     * El cliente puede identificarse por cliente_id o, si no se envía, por
     * documento (dni/cuit/cuil) o correo, siempre dentro de la empresa del token.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            /** @var Empresa $empresa */
            $empresa = $request->attributes->get('empresa');

            if (! $empresa) {
                return $this->errorResponse(401, 'Empresa no autenticada.');
            }

            $validated = $request->validate([
                'empresa_id' => 'nullable|integer|exists:empresas,id',
                'cliente_id' => 'nullable|integer|exists:clientes,id',
                'dni' => 'nullable|string',
                'cuit' => 'nullable|string',
                'cuil' => 'nullable|string',
                'correo' => 'nullable|email',
                'nombre_cerradura' => 'required|string|max:255',
                'fecha_hora' => 'required|date',
                'tipo' => 'nullable|string|max:255',
                'dispositivo_id' => 'nullable|string|max:255',
                'datos_extra' => 'nullable|array',
            ]);

            // Si el DLL envía el empresa_id, debe coincidir con la empresa del token.
            if (isset($validated['empresa_id']) && (int) $validated['empresa_id'] !== (int) $empresa->id) {
                return $this->errorResponse(403, 'El empresa_id no corresponde al token de autenticación.');
            }

            // Resolver el cliente (cliente_id > documento > correo).
            $resolucion = $this->resolverClienteId($validated, $empresa);

            if ($resolucion['error']) {
                return $this->errorResponse($resolucion['code'], $resolucion['message']);
            }

            $registro = RegistroCerradura::create([
                'empresa_id' => $empresa->id,
                'cliente_id' => $resolucion['cliente_id'],
                'nombre_cerradura' => $validated['nombre_cerradura'],
                'fecha_hora' => $validated['fecha_hora'],
                'tipo' => $validated['tipo'] ?? null,
                'dispositivo_id' => $validated['dispositivo_id'] ?? null,
                'datos_extra' => $validated['datos_extra'] ?? null,
                'recibido_en' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registro de cerradura guardado exitosamente.',
                'data' => $registro,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'code' => 422,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return $this->errorResponse(500, 'Error al guardar el registro de cerradura', $e->getMessage());
        }
    }

    /**
     * Resuelve el cliente del registro dentro de la empresa autenticada.
     * Prioridad: cliente_id explícito > documento (dni/cuit/cuil) > correo.
     *
     * @return array{cliente_id:int|null, error:bool, code:int, message:string|null}
     */
    private function resolverClienteId(array $validated, Empresa $empresa): array
    {
        $clienteId = $validated['cliente_id'] ?? null;
        $documento = $validated['dni'] ?? $validated['cuit'] ?? $validated['cuil'] ?? null;
        $correo = $validated['correo'] ?? null;

        // Debe venir al menos un identificador.
        if (empty($clienteId) && empty($documento) && empty($correo)) {
            return $this->resolucionError(
                422,
                'Debe enviar cliente_id o un dato de identificación (dni/cuit/cuil o correo).'
            );
        }

        // 1. cliente_id explícito: debe pertenecer a la empresa del token.
        if (! empty($clienteId)) {
            $pertenece = $empresa->clientes()
                ->where('clientes.id', $clienteId)
                ->exists();

            if (! $pertenece) {
                return $this->resolucionError(403, 'El cliente no está vinculado a la empresa especificada.');
            }

            return $this->resolucionOk((int) $clienteId);
        }

        // 2. Documento (dni/cuit/cuil). El cliente puede estar guardado como DNI
        //    o como CUIT/CUIL, y el DLL puede enviar solo el DNI que forma parte
        //    de ese CUIT/CUIL (o al revés), por lo que se normaliza en ambos lados.
        if (! empty($documento)) {
            $docNormalizado = DniHelper::extractDni($documento);

            if (! empty($docNormalizado)) {
                // Valor útil para el pre-filtro SQL (sin ceros a la izquierda).
                $docSql = ltrim($docNormalizado, '0');
                $docSql = $docSql === '' ? $docNormalizado : $docSql;

                $candidatos = $empresa->clientes()
                    ->where(function ($q) use ($docSql) {
                        $q->where('clientes.dni', $docSql)
                            ->orWhere('clientes.dni', 'like', '%'.$docSql.'%');
                    })
                    ->get()
                    ->filter(function ($cliente) use ($docNormalizado) {
                        return $this->documentoCoincide($cliente->dni, $docNormalizado);
                    })
                    ->values();

                if ($candidatos->count() === 1) {
                    return $this->resolucionOk((int) $candidatos->first()->id);
                }

                if ($candidatos->count() > 1) {
                    return $this->resolucionError(
                        422,
                        'Se encontraron varios clientes con el documento indicado. Envíe cliente_id.'
                    );
                }
            }
        }

        // 3. Correo (match exacto).
        if (! empty($correo)) {
            $candidatos = $empresa->clientes()
                ->where('clientes.correo', $correo)
                ->get()
                ->values();

            if ($candidatos->count() === 1) {
                return $this->resolucionOk((int) $candidatos->first()->id);
            }

            if ($candidatos->count() > 1) {
                return $this->resolucionError(
                    422,
                    'Se encontraron varios clientes con el correo indicado. Envíe cliente_id.'
                );
            }
        }

        return $this->resolucionError(422, 'No se pudo identificar el cliente con los datos proporcionados.');
    }

    /**
     * Compara el documento guardado con el normalizado (DNI de 8 dígitos),
     * contemplando que el guardado sea DNI o CUIT/CUIL y ceros a la izquierda.
     */
    private function documentoCoincide($guardado, string $docNormalizado): bool
    {
        if ($guardado === null || $guardado === '') {
            return false;
        }

        $guardadoStr = (string) $guardado;
        $objetivo = ltrim($docNormalizado, '0');
        $normalizado = ltrim($guardadoStr, '0');

        if ($objetivo !== '' && $normalizado === $objetivo) {
            return true;
        }

        return DniHelper::extractDni($guardado) === $docNormalizado;
    }

    /**
     * @return array{cliente_id:int, error:bool, code:int, message:null}
     */
    private function resolucionOk(int $clienteId): array
    {
        return [
            'cliente_id' => $clienteId,
            'error' => false,
            'code' => 200,
            'message' => null,
        ];
    }

    /**
     * @return array{cliente_id:null, error:bool, code:int, message:string}
     */
    private function resolucionError(int $code, string $message): array
    {
        return [
            'cliente_id' => null,
            'error' => true,
            'code' => $code,
            'message' => $message,
        ];
    }

    /**
     * Respuesta JSON de error unificada: success + code (HTTP) + message.
     */
    private function errorResponse(int $code, string $message, ?string $detalle = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];

        if ($detalle !== null) {
            $payload['error'] = $detalle;
        }

        return response()->json($payload, $code);
    }
}
