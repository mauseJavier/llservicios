<?php

namespace App\Imports;



use Illuminate\Support\Collection;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ReciboSueldoImport implements WithHeadingRow
{

    // public function model(array $row)
    // {
    //     return[
    //         'codigo'  => $row['codigo'],
    //         'detalle' => $row['detalle'],
    //         'precio'    => $row['precio_venta'],
    //     ];
    // }


    // /**
    // * @param Collection $collection
    // */
    // public function collection(Collection $collection)
    // {
    //     // dd($collection);

    //     foreach ($collection as $key => $value) {
    //        dd($value['codigo']);
    //     }
        
    // }
}
