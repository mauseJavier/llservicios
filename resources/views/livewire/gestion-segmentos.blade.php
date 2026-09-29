<div>
    <div class="container">
        <h1>Segmentos</h1>

        <nav>
            <ul>
                <li>
                    <form wire:submit.prevent="">
                        <div class="input-group">
                            <input type="search" class="input" wire:model.live="buscar" placeholder="Buscar segmentos...">
                        </div>
                    </form>
                </li>
            </ul>
            <ul>
                <li>
                    <button wire:click="crear" role="button">
                        <i class="fas fa-plus"></i> Nuevo Segmento
                    </button>
                </li>
            </ul>
        </nav>

        @if (session()->has('status'))
            <article style="background-color: var(--pico-color-green-50); border-left: 4px solid var(--pico-color-green-500); padding: 0.75rem; margin-bottom: 1rem;">
                <i class="fas fa-check-circle" style="color: var(--pico-color-green-500);"></i>
                {{ session('status') }}
            </article>
        @endif

        <figure class="overflow-auto">
            <table>
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Color</th>
                        <th scope="col">Clientes</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($segmentos as $s)
                        <tr>
                            <td>{{ $s->id }}</td>
                            <td>
                                <strong>{{ $s->nombre }}</strong>
                            </td>
                            <td>
                                <small>{{ $s->descripcion ? Str::limit($s->descripcion, 60) : '—' }}</small>
                            </td>
                            <td>
                                @if ($s->color)
                                    <span style="display: inline-block; width: 24px; height: 24px; border-radius: 4px; background-color: {{ $s->color }}; vertical-align: middle; border: 1px solid rgba(0,0,0,0.1);"></span>
                                    <small style="vertical-align: middle; margin-left: 4px;">{{ $s->color }}</small>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                {{ $s->clientes()->count() }}
                            </td>
                            <td>
                                <button wire:click="editar({{ $s->id }})" class="outline" style="padding: 0.25rem 0.5rem; font-size: 0.85em;" data-tooltip="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="confirmarEliminar({{ $s->id }})" wire:confirm="¿Estás seguro de eliminar el segmento '{{ $s->nombre }}'?" class="outline secondary" style="padding: 0.25rem 0.5rem; font-size: 0.85em;" data-tooltip="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center;">
                                <em>No hay segmentos creados todavía.
                                    @if($buscar)
                                        <br>Intenta con otro término de búsqueda.
                                    @else
                                        <br><button wire:click="crear" class="outline">Crear el primer segmento</button>
                                    @endif
                                </em>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </figure>

        @if ($segmentos->hasPages())
            <nav>
                <ul>
                    <li><strong>Pag. {{ $segmentos->currentPage() }} de {{ $segmentos->lastPage() }}, Total: {{ $segmentos->total() }}</strong></li>
                </ul>
                <ul>
                    <li><a href="{{ $segmentos->previousPageUrl() }}" role="button">Anterior</a></li>
                    @if ($segmentos->currentPage() - 1 != 0)
                        <li><a href="{{ $segmentos->url($segmentos->currentPage() - 1) }}">{{ $segmentos->currentPage() - 1 }}</a></li>
                    @endif
                    <li><strong><a href="{{ $segmentos->url($segmentos->currentPage()) }}">{{ $segmentos->currentPage() }}</a></strong></li>
                    @if ($segmentos->currentPage() + 1 <= $segmentos->lastPage())
                        <li><a href="{{ $segmentos->url($segmentos->currentPage() + 1) }}">{{ $segmentos->currentPage() + 1 }}</a></li>
                    @endif
                    <li><a href="{{ $segmentos->nextPageUrl() }}" role="button">Siguiente</a></li>
                </ul>
            </nav>
        @endif
    </div>

    @if ($mostrarFormulario)
        <dialog open>
            <article style="max-width: 500px; margin: 0 auto;">
                <header>
                    <button aria-label="Close" rel="prev" wire:click="cerrarFormulario" style="border: none; background: none; cursor: pointer; font-size: 1.5em;"></button>
                    <strong><i class="fas {{ $editando ? 'fa-edit' : 'fa-plus' }}"></i> {{ $editando ? 'Editar Segmento' : 'Nuevo Segmento' }}</strong>
                </header>

                <form wire:submit.prevent="guardar">
                    <label for="nombre">
                        Nombre *
                        <input type="text" id="nombre" wire:model="nombre" placeholder="Ej: VIP, Inactivo, Comercio..." required>
                        @error('nombre') <small style="color: var(--pico-color-red-500);">{{ $message }}</small> @enderror
                    </label>

                    <label for="descripcion">
                        Descripción
                        <textarea id="descripcion" wire:model="descripcion" rows="3" placeholder="Descripción opcional del segmento..."></textarea>
                        @error('descripcion') <small style="color: var(--pico-color-red-500);">{{ $message }}</small> @enderror
                    </label>

                    <label for="color">
                        Color
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="color" id="color" wire:model="color" style="width: 48px; height: 38px; padding: 2px; cursor: pointer;">
                            <input type="text" wire:model="color" style="width: 100px;" placeholder="#3b82f6">
                        </div>
                        @error('color') <small style="color: var(--pico-color-red-500);">{{ $message }}</small> @enderror
                    </label>

                    <footer style="display: flex; justify-content: flex-end; gap: 1rem;">
                        <button type="button" class="secondary" wire:click="cerrarFormulario">Cancelar</button>
                        <button type="submit">
                            <i class="fas {{ $editando ? 'fa-save' : 'fa-plus' }}"></i>
                            {{ $editando ? 'Guardar Cambios' : 'Crear Segmento' }}
                        </button>
                    </footer>
                </form>
            </article>
        </dialog>
    @endif

    <style>
        .overflow-auto {
            overflow-x: auto;
        }

        dialog {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            padding: 1rem;
        }

        dialog article {
            max-height: 90vh;
            overflow-y: auto;
            background-color: var(--pico-background-color);
        }
    </style>
</div>
