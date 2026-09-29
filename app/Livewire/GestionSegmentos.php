<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Segmento;
use Illuminate\Support\Facades\Auth;

class GestionSegmentos extends Component
{
    use WithPagination;

    public $buscar = '';
    public $mostrarFormulario = false;
    public $editando = false;
    public $segmentoId = null;
    public $nombre = '';
    public $descripcion = '';
    public $color = '#3b82f6';

    protected $rules = [
        'nombre' => 'required|string|max:255',
        'descripcion' => 'nullable|string|max:1000',
        'color' => 'nullable|string|max:20',
    ];

    protected $messages = [
        'nombre.required' => 'El nombre del segmento es obligatorio.',
        'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
    ];

    public function mount()
    {
        $this->resetearBusqueda();
    }

    public function render()
    {
        $usuario = Auth::user();

        $segmentos = Segmento::where('empresa_id', $usuario->empresa_id)
            ->when($this->buscar, function ($query) {
                $query->where('nombre', 'like', '%' . $this->buscar . '%');
            })
            ->orderBy('id', 'DESC')
            ->paginate(15);

        return view('livewire.gestion-segmentos', compact('segmentos'))
            ->extends('principal.principal')
            ->section('body');
    }

    public function updatedBuscar()
    {
        $this->resetPage();
    }

    public function crear()
    {
        $this->resetearFormulario();
        $this->editando = false;
        $this->segmentoId = null;
        $this->mostrarFormulario = true;
    }

    public function editar(Segmento $segmento)
    {
        $this->verificarPerteneceAEmpresa($segmento);
        $this->resetearFormulario();
        $this->editando = true;
        $this->segmentoId = $segmento->id;
        $this->nombre = $segmento->nombre;
        $this->descripcion = $segmento->descripcion;
        $this->color = $segmento->color ?? '#3b82f6';
        $this->mostrarFormulario = true;
    }

    public function guardar()
    {
        $this->validate();

        $usuario = Auth::user();

        if ($this->editando) {
            $segmento = Segmento::findOrFail($this->segmentoId);
            $this->verificarPerteneceAEmpresa($segmento);
            $segmento->update([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'color' => $this->color,
            ]);
            session()->flash('status', 'Segmento "' . $segmento->nombre . '" actualizado correctamente.');
        } else {
            $segmento = Segmento::create([
                'empresa_id' => $usuario->empresa_id,
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'color' => $this->color,
            ]);
            session()->flash('status', 'Segmento "' . $segmento->nombre . '" creado correctamente.');
        }

        $this->cerrarFormulario();
    }

    public function confirmarEliminar(Segmento $segmento)
    {
        $this->verificarPerteneceAEmpresa($segmento);
        $nombre = $segmento->nombre;
        $segmento->delete();
        session()->flash('status', 'Segmento "' . $nombre . '" eliminado correctamente.');
    }

    public function cerrarFormulario()
    {
        $this->mostrarFormulario = false;
        $this->resetearFormulario();
    }

    private function resetearFormulario()
    {
        $this->nombre = '';
        $this->descripcion = '';
        $this->color = '#3b82f6';
        $this->resetValidation();
    }

    private function resetearBusqueda()
    {
        $this->buscar = '';
    }

    private function verificarPerteneceAEmpresa(Segmento $segmento): void
    {
        if ($segmento->empresa_id !== Auth::user()->empresa_id) {
            abort(403, 'No tienes permiso para modificar este segmento.');
        }
    }
}
