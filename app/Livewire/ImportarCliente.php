<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helpers\DniHelper;

class ImportarCliente extends Component
{
    use WithFileUploads;

    public $archivoCSV;
    public $procesando = false;
    public $resumen = null;
    public $errores = [];
    public $mostrarInstrucciones = true;

    protected $rules = [
        'archivoCSV' => 'required|file|mimes:csv,txt|max:2048',
    ];

    protected $messages = [
        'archivoCSV.required' => 'Debe seleccionar un archivo CSV',
        'archivoCSV.file' => 'El archivo no es válido',
        'archivoCSV.mimes' => 'El archivo debe ser de tipo CSV (.csv o .txt)',
        'archivoCSV.max' => 'El archivo no debe superar los 2MB',
    ];

    public function toggleInstrucciones()
    {
        $this->mostrarInstrucciones = !$this->mostrarInstrucciones;
    }

    public function importarClientes()
    {
        $this->validate();

        $this->procesando = true;
        $this->resumen = null;
        $this->errores = [];

        try {
            $usuario = Auth::user();

            // Obtener la empresa del usuario
            $empresa = Empresa::find($usuario->empresa_id);

            if (!$empresa) {
                session()->flash('error', 'No se encontró la empresa asociada al usuario');
                $this->procesando = false;
                return;
            }

            // Leer el archivo CSV
            $path = $this->archivoCSV->getRealPath();
            $file = fopen($path, 'r');

            if (!$file) {
                session()->flash('error', 'No se pudo leer el archivo');
                $this->procesando = false;
                return;
            }

            // Leer la primera línea (encabezados)
            $headers = fgetcsv($file, 0, ',');

            if (!$headers) {
                session()->flash('error', 'El archivo CSV está vacío o no tiene el formato correcto');
                fclose($file);
                $this->procesando = false;
                return;
            }

            // Normalizar encabezados (sin BOM, minúsculas y sin espacios) para mapear por nombre
            $headers = array_map(function ($header) {
                $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
                return strtolower(trim($header));
            }, $headers);

            // Solo se exige la columna "nombre". El resto se mapea por nombre, por lo que
            // el orden y las columnas extra (p. ej. el CSV exportado) no importan.
            if (!in_array('nombre', $headers, true)) {
                session()->flash('error', 'El CSV debe contener al menos la columna "nombre". Columnas reconocidas: nombre, correo, telefono, dni, domicilio, titular, condicion_iva_id, tipo_documento_id.');
                fclose($file);
                $this->procesando = false;
                return;
            }

            // Devuelve el valor de una columna de la fila según el nombre del encabezado
            $obtener = function (array $fila, string $columna) use ($headers) {
                $indice = array_search($columna, $headers, true);

                if ($indice === false) {
                    return null;
                }

                $valor = $fila[$indice] ?? null;

                return is_string($valor) ? trim($valor) : $valor;
            };

            $estadisticas = [
                'total_filas' => 0,
                'creados' => 0,
                'actualizados' => 0,
                'errores' => 0,
                'omitidos' => 0,
            ];

            $linea = 1; // Empezamos en 1 porque ya leímos los encabezados

            // Procesar cada línea del CSV
            while (($data = fgetcsv($file, 0, ',')) !== false) {
                $linea++;
                $estadisticas['total_filas']++;

                // Saltar líneas vacías
                if (empty(array_filter($data))) {
                    $estadisticas['omitidos']++;
                    continue;
                }

                // Crear array asociativo con los datos (mapeo por nombre de columna)
                $correo = $obtener($data, 'correo');

                $datosCliente = [
                    'nombre' => $obtener($data, 'nombre'),
                    'correo' => ($correo !== null && $correo !== '') ? $correo : 'correo@correo.com',
                    'telefono' => $obtener($data, 'telefono'),
                    'dni' => $obtener($data, 'dni'),
                    'domicilio' => $obtener($data, 'domicilio'),
                ];

                // Validar los datos obligatorios
                $validator = Validator::make($datosCliente, [
                    'nombre' => 'required|string|max:255',
                    // 'correo' => 'required|email|max:255',
                    'correo' => 'required|max:255',
                    'telefono' => 'nullable|string|max:255',
                    'dni' => 'nullable|numeric|digits_between:1,11',
                    'domicilio' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    $estadisticas['errores']++;
                    $this->errores[] = [
                        'linea' => $linea,
                        'datos' => $datosCliente,
                        'mensajes' => $validator->errors()->all(),
                    ];
                    continue;
                }

                // Columnas opcionales (solo se aplican si existen en el CSV)
                $titular = $obtener($data, 'titular');
                $condicionIva = $obtener($data, 'condicion_iva_id');
                $tipoDocumento = $obtener($data, 'tipo_documento_id');

                try {
                    // Buscar cliente existente por DNI o nombre (normalizando DNI/CUIT)
                    $clienteExistente = null;

                    if (!empty($datosCliente['dni'])) {
                        $dniNormalizado = DniHelper::extractDni($datosCliente['dni']);
                        $clienteExistente = Cliente::where(function ($query) use ($dniNormalizado) {
                            $query->where('dni', $dniNormalizado)
                                  ->orWhere('dni', 'like', '%' . $dniNormalizado . '%');
                        })->first();
                    }

                    if (!$clienteExistente && !empty($datosCliente['nombre'])) {
                        $clienteExistente = Cliente::where('nombre', $datosCliente['nombre'])->first();
                    }

                    if ($clienteExistente) {
                        // Actualizar cliente existente
                        $actualizar = [
                            'nombre' => $datosCliente['nombre'],
                            'correo' => $datosCliente['correo'],
                            'telefono' => $datosCliente['telefono'],
                            'dni' => $datosCliente['dni'],
                            'domicilio' => $datosCliente['domicilio'],
                        ];

                        if ($titular !== null) {
                            $actualizar['titular'] = $titular;
                        }

                        if ($condicionIva !== null && $condicionIva !== '') {
                            $actualizar['condicion_iva_id'] = (int) $condicionIva;
                        }

                        if ($tipoDocumento !== null && $tipoDocumento !== '') {
                            $actualizar['tipo_documento_id'] = (int) $tipoDocumento;
                        }

                        $clienteExistente->update($actualizar);

                        // Verificar si ya está vinculado a la empresa
                        $yaVinculado = DB::table('cliente_empresa')
                            ->where('cliente_id', $clienteExistente->id)
                            ->where('empresa_id', $empresa->id)
                            ->exists();

                        if (!$yaVinculado) {
                            // Vincular a la empresa
                            $clienteExistente->empresas()->attach($empresa->id);
                        }

                        $estadisticas['actualizados']++;
                    } else {
                        // Crear nuevo cliente
                        $nuevo = [
                            'nombre' => $datosCliente['nombre'],
                            'correo' => $datosCliente['correo'],
                            'telefono' => $datosCliente['telefono'],
                            'dni' => $datosCliente['dni'],
                            'domicilio' => $datosCliente['domicilio'],
                            'condicion_iva_id' => ($condicionIva !== null && $condicionIva !== '') ? (int) $condicionIva : 5,
                            'tipo_documento_id' => ($tipoDocumento !== null && $tipoDocumento !== '')
                                ? (int) $tipoDocumento
                                : DniHelper::tipoDocumentoReceptor($datosCliente['dni'] ?? null),
                        ];

                        if ($titular !== null) {
                            $nuevo['titular'] = $titular;
                        }

                        $nuevoCliente = Cliente::create($nuevo);

                        // Vincular a la empresa del usuario
                        $nuevoCliente->empresas()->attach($empresa->id);

                        $estadisticas['creados']++;
                    }
                } catch (\Exception $e) {
                    $estadisticas['errores']++;
                    $this->errores[] = [
                        'linea' => $linea,
                        'datos' => $datosCliente,
                        'mensajes' => ['Error al procesar: ' . $e->getMessage()],
                    ];
                }
            }

            fclose($file);

            // Preparar resumen
            $this->resumen = $estadisticas;

            if ($estadisticas['creados'] > 0 || $estadisticas['actualizados'] > 0) {
                session()->flash('success', 'Importación completada exitosamente');
            }

            if ($estadisticas['errores'] > 0) {
                session()->flash('warning', 'La importación se completó con algunos errores. Revise el resumen.');
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Error al procesar el archivo: ' . $e->getMessage());
        } finally {
            $this->procesando = false;
        }
    }

    public function limpiar()
    {
        $this->reset(['archivoCSV', 'resumen', 'errores']);
    }

    public function descargarPlantilla()
    {
        $nombreArchivo = 'plantilla_importar_clientes.csv';
        $contenido = "nombre,correo,telefono,dni,domicilio,titular,condicion_iva_id,tipo_documento_id\n";
        $contenido .= "Juan Pérez,juan.perez@email.com,3516123456,12345678,Av. Siempre Viva 123,Juan Pérez,5,1\n";
        $contenido .= "María González,maria.gonzalez@email.com,3517654321,87654321,Calle Falsa 456,,5,\n";

        return response()->streamDownload(function () use ($contenido) {
            echo $contenido;
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
        ]);
    }

    public function render()
    {
        return view('livewire.importar-cliente')
            ->extends('principal.principal')
            ->section('body');
    }
}
