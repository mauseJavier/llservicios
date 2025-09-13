<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Expense::query();
        
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('detalle', 'like', '%' . $buscar . '%')
                  ->orWhere('forma_pago', 'like', '%' . $buscar . '%')
                  ->orWhere('estado', 'like', '%' . $buscar . '%')
                  ->orWhere('comentario', 'like', '%' . $buscar . '%');
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
        return view('expenses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'detalle' => 'required|string|max:255',
            'forma_pago' => 'required|string|max:255',
            'estado' => 'required|string|max:255',
            'importe' => 'required|numeric|min:0',
            'comentario' => 'nullable|string',
        ]);

        Expense::create($request->all());

        return redirect()->route('expenses.index')->with('success', 'Gasto creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $expense = Expense::findOrFail($id);
        return view('expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $expense = Expense::findOrFail($id);
        return view('expenses.edit', compact('expense'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'detalle' => 'required|string|max:255',
            'forma_pago' => 'required|string|max:255',
            'estado' => 'required|string|max:255',
            'importe' => 'required|numeric|min:0',
            'comentario' => 'nullable|string',
        ]);

        $expense = Expense::findOrFail($id);
        $expense->update($request->all());

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
