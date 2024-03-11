<?php

namespace App\Imports;

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
        ]);
    }
}