<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\FormaPago;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Expense::with(['formaPago', 'usuario'])
            ->where('empresa_id', Auth::id());
        
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
            $q->where('detalle', 'like', '%' . $buscar . '%')
              ->orWhere('estado', 'like', '%' . $buscar . '%')
              ->orWhere('comentario', 'like', '%' . $buscar . '%')
              ->orWhere('usuario_nombre', 'like', '%' . $buscar . '%')
              ->orWhereHas('formaPago', function($subQuery) use ($buscar) {
                  $subQuery->where('nombre', 'like', '%' . $buscar . '%');
              });
            });
        }
        
        $expenses = $query->orderBy('id', 'DESC')->paginate(15);
        $expenses->appends(['buscar' => $request->buscar]);
        
        return view('expenses.index', compact('expenses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $formasPago = FormaPago::all();
        return view('expenses.create', compact('formasPago'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'detalle' => 'required|string|max:255',
            'forma_pago_id' => 'required|exists:forma_pagos,id',
            'estado' => 'required|in:pago,impago',
            'importe' => 'required|numeric|min:0',
            'comentario' => 'nullable|string',
        ]);

        $expenseData = $request->all();
        $expenseData['usuario_id'] = Auth::id();
        $expenseData['usuario_nombre'] = Auth::user()->name;
        $expenseData['empresa_id'] = Auth::user()->empresa_id;

        Expense::create($expenseData);

        return redirect()->route('expenses.index')->with('success', 'Gasto creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $expense = Expense::with(['formaPago', 'usuario'])->findOrFail($id);
        return view('expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $expense = Expense::findOrFail($id);
        $formasPago = FormaPago::all();
        return view('expenses.edit', compact('expense', 'formasPago'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'detalle' => 'required|string|max:255',
            'forma_pago_id' => 'required|exists:forma_pagos,id',
            'estado' => 'required|in:pago,impago',
            'importe' => 'required|numeric|min:0',
            'comentario' => 'nullable|string',
        ]);

        $expenseData = $request->all();
        $expenseData['usuario_id'] = Auth::id();
        $expenseData['usuario_nombre'] = Auth::user()->name;

        $expense = Expense::findOrFail($id);
        $expense->update($expenseData);

        return redirect()->route('expenses.index')->with('success', 'Gasto actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Gasto eliminado exitosamente');
    }
}
