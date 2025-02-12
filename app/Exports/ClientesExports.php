<?php

namespace App\Exports;

use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class ClientesExports implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Cliente::all();
    }

    public function startCell(): string
    {
        return 'A2'; // Comienza en la celda A2 (después del encabezado)
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Correo Electrónico',
            'DNI',
            'Domicilio',
            'Creado',
            'Actualizado'
            // Agrega más columnas según tus necesidades
        ];
    }

}
