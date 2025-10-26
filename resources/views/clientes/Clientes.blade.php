@extends('principal.principal')

@section('body')


<div class="container">
  
  <h1>Clientes</h1>
    
    {{-- <nav>
    <div style="display: flex; align-items: stretch; flex-wrap: wrap;">

    <a href="{{route('Cliente.create')}}" role="button" data-tooltip="Nuevo Cliente" style="display: flex; align-items: center; justify-content: center; background: #28a745; color: #fff; border-radius:  8px ; width: 48px; height: 48px; font-size: 2em; text-decoration: none; border: 1px solid #28a745; cursor: pointer; margin-top: 0;">
      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
    </a>

    <a href="{{route('ImportarClientes')}}" role="button" data-tooltip="Importar CSV" style="display: flex; align-items: center; justify-content: center; background: #198754; color: #fff; border-radius: 8px; width: 48px; height: 48px; font-size: 1.5em; text-decoration: none; border: 1px solid #198754; margin-left: 8px; margin-top: 1px; cursor: pointer;">
        <i class="fas fa-file-excel"></i>
      </a>
    <a href="{{route('ExportarClientes')}}" role="button" data-tooltip="Exportar CSV" style="display: flex; align-items: center; justify-content: center; background: #FFD43B; color: #333; border-radius: 8px; width: 48px; height: 48px; font-size: 1.5em; text-decoration: none; border: 1px solid #FFD43B; margin-left: 8px; margin-top: 1px; cursor: pointer;">
        <i class="fas fa-file-excel"></i>
      </a>
    </div>
  </nav> --}}

  <br>

  <livewire:ver-cliente.ver-cliente />

</div>







</div>


@endsection 

