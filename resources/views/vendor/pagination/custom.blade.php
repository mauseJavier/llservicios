@if ($paginator->hasPages())
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        {{-- Información de registros --}}
        <div style="font-size: 0.9rem; color: #6c757d;">
            Mostrando <strong>{{ $paginator->firstItem() }}</strong> a <strong>{{ $paginator->lastItem() }}</strong> de <strong>{{ $paginator->total() }}</strong> registros
        </div>

        {{-- Controles de navegación --}}
        <nav role="navigation" aria-label="Navegación de Paginación">
            <ul style="display: flex; list-style: none; gap: 0.3rem; padding: 0; margin: 0;">
                {{-- Botón Anterior --}}
                @if ($paginator->onFirstPage())
                    <li>
                        <span style="padding: 0.3rem 0.6rem; font-size: 0.85rem; border: 1px solid #ccc; border-radius: 4px; opacity: 0.5; display: inline-block;">
                            ← Anterior
                        </span>
                    </li>
                @else
                    <li>
                        <button wire:click="previousPage" wire:loading.attr="disabled" rel="prev" 
                                style="padding: 0.3rem 0.6rem; font-size: 0.85rem; border: 1px solid #007bff; background-color: transparent; border-radius: 4px; cursor: pointer; color: #007bff;">
                            ← Anterior
                        </button>
                    </li>
                @endif

                {{-- Números de página --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li>
                            <span style="padding: 0.3rem 0.6rem; font-size: 0.85rem; opacity: 0.5;">{{ $element }}</span>
                        </li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li>
                                    <span style="padding: 0.3rem 0.6rem; font-size: 0.85rem; background-color: #007bff; color: white; border-radius: 4px; display: inline-block; font-weight: bold; min-width: 32px; text-align: center;">
                                        {{ $page }}
                                    </span>
                                </li>
                            @else
                                <li>
                                    <button wire:click="gotoPage({{ $page }})" 
                                            style="padding: 0.3rem 0.6rem; font-size: 0.85rem; border: 1px solid #007bff; background-color: transparent; border-radius: 4px; cursor: pointer; color: #007bff; min-width: 32px;">
                                        {{ $page }}
                                    </button>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Botón Siguiente --}}
                @if ($paginator->hasMorePages())
                    <li>
                        <button wire:click="nextPage" wire:loading.attr="disabled" rel="next"
                                style="padding: 0.3rem 0.6rem; font-size: 0.85rem; border: 1px solid #007bff; background-color: transparent; border-radius: 4px; cursor: pointer; color: #007bff;">
                            Siguiente →
                        </button>
                    </li>
                @else
                    <li>
                        <span style="padding: 0.3rem 0.6rem; font-size: 0.85rem; border: 1px solid #ccc; border-radius: 4px; opacity: 0.5; display: inline-block;">
                            Siguiente →
                        </span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif
