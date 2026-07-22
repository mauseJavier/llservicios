@extends('principal.principal')

@section('body')
<article>
    <header>
        <hgroup>
            <h2>Ver Logs</h2>
            <p>Archivo: storage/logs/laravel.log</p>
        </hgroup>
    </header>

    <div style="display: flex; gap: 1rem; align-items: end; margin-bottom: 1rem;">
        <form action="{{ route('logs.index') }}" method="GET" style="display: flex; gap: 0.5rem; align-items: end;">
            <label for="lines">
                Líneas
                <input type="number" name="lines" id="lines" value="{{ $lines }}" min="10" max="10000" style="width: 100px;">
            </label>
            <button type="submit">Ver</button>
        </form>

        <form action="{{ route('logs.clear') }}" method="POST" onsubmit="return confirm('¿Estás seguro de borrar todos los logs?')">
            @csrf
            @method('DELETE')
            <button type="submit" style="background-color: red; color: white;">Borrar Logs</button>
        </form>
    </div>

    <pre style="max-height: 70vh; overflow-y: auto; background: #1e1e2e; color: #cdd6f4; padding: 1rem; border-radius: 0.5rem; font-size: 0.8rem; line-height: 1.4;"><code>@foreach($logContent as $line){{ $line }}@endforeach</code></pre>
</article>
@endsection
