<?php

namespace App\Imports;

use App\Helpers\DniHelper;
use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ClienteImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Cliente([
            //
            'nombre'=> $row['nombre'],
            'correo'=> $row['correo'],
            'dni'=> $row['dni'],
            'domicilio'=> $row['domicilio'],
            'telefono'=> $row['telefono'],
            'condicion_iva_id'=> $row['condicion_iva_id'] ?? 5,
            'tipo_documento_id'=> ($row['tipo_documento_id'] ?? null) ?: DniHelper::tipoDocumentoReceptor($row['dni'] ?? null),
        ]);
    }
}