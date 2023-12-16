<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagosRequest;
use App\Http\Requests\UpdatePagosRequest;
use App\Models\Pagos;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PagosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pagos = DB::select('SELECT
                                    a.*,
                                    c.name AS nombreUsuario,
                                    d.nombre AS Servicio,
                                    e.nombre AS Cliente
                                FROM
                                    pagos a,
                                    servicio_pagar b,
                                    users c,
                                    servicios d,
                                    clientes e
                                WHERE
                                    a.id_servicio_pagar = b.id AND a.id_usuario = c.id AND b.servicio_id = d.id AND b.cliente_id = e.id');

        return $pagos;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePagosRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Pagos $pagos)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pagos $pagos)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePagosRequest $request, Pagos $pagos)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pagos $pagos)
    {
        //
    }
}
