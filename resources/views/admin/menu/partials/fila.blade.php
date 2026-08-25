@php
    /** @var \App\Models\MenuItem $item */
    $destino = $item->destination();
    $rotulo = $item->label;

    // Una ruta interna hacia un tema que todavía no se ha importado sigue
    // sirviéndose del portal anterior. No es un fallo y no hay que arreglarlo
    // aquí —se arregla importando el tema—, pero conviene que se vea.
    $sinMigrar = $item->path !== null
        && App\Support\LegacyLink::resolve(['path' => $item->path])['external'];
@endphp

<div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-[10px] {{ $item->is_active ? '' : 'opacity-60' }}">

    {{-- Subir y bajar. Una petición por clic y sin JavaScript: el menú se
         ordena una vez cada varios meses y así funciona con el teclado, sin
         guion y en un navegador viejo. --}}
    <div class="flex shrink-0 flex-col">
        @foreach (['arriba' => 'M18 15l-6-6-6 6', 'abajo' => 'm6 9 6 6 6-6'] as $direccion => $flecha)
            <form method="POST" action="{{ route('admin.menu.move', $item) }}">
                @csrf
                <input type="hidden" name="direccion" value="{{ $direccion }}">
                <button type="submit"
                        aria-label="{{ __('admin-menu.accion.'.($direccion === 'arriba' ? 'subir' : 'bajar'), ['rotulo' => $rotulo]) }}"
                        class="flex size-6 items-center justify-center border-0 bg-transparent text-link hover:text-heading">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="{{ $flecha }}" />
                    </svg>
                </button>
            </form>
        @endforeach
    </div>

    {{-- Rótulo y destino --}}
    <div class="min-w-[220px] flex-1">
        <p class="m-0 text-14 font-semibold text-heading">{{ $rotulo }}</p>

        <p class="m-0 mt-[2px] flex flex-wrap items-center gap-x-3 gap-y-1 text-12-5 text-faint">
            @if ($destino)
                <span class="break-all">{{ $destino }}</span>
            @else
                <span>{{ __('admin-menu.fila.sin_destino') }}</span>
            @endif

            @if (! $item->is_active)
                <span class="rounded-full bg-tint px-2 py-[1px] font-semibold text-body">
                    {{ __('admin-menu.fila.oculta') }}
                </span>
            @endif

            @if ($item->url)
                <span>{{ __('admin-menu.fila.externo') }}</span>
            @elseif ($sinMigrar)
                <span>{{ __('admin-menu.fila.sin_migrar') }}</span>
            @endif
        </p>
    </div>

    {{-- Acciones. Cada una en su propio formulario: no se pueden anidar. --}}
    <div class="flex shrink-0 flex-wrap items-center gap-3 text-13">
        <form method="POST" action="{{ route('admin.menu.toggle', $item) }}">
            @csrf
            <button type="submit"
                    class="border-0 bg-transparent p-0 font-semibold text-link underline underline-offset-4">
                {{ $item->is_active ? __('admin-menu.accion.ocultar_corto') : __('admin-menu.accion.mostrar_corto') }}
                <span class="sr-only">{{ $rotulo }}</span>
            </button>
        </form>

        <a href="{{ route('admin.menu.edit', $item) }}"
           class="font-semibold text-link underline underline-offset-4">
            {{ __('admin-menu.accion.editar_corto') }}
            <span class="sr-only">{{ $rotulo }}</span>
        </a>

        <form method="POST" action="{{ route('admin.menu.destroy', $item) }}"
              onsubmit="return confirm(@js(__('admin-menu.accion.confirmar_borrado', ['rotulo' => $rotulo])))">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="border-0 bg-transparent p-0 font-semibold text-danger underline underline-offset-4">
                {{ __('admin-menu.accion.borrar_corto') }}
                <span class="sr-only">{{ $rotulo }}</span>
            </button>
        </form>
    </div>
</div>
