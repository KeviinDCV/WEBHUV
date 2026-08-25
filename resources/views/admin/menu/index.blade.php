@php
    use App\Models\MenuItem;

    $backUrl = route('home');
@endphp

@extends('layouts.admin')

@section('title', __('admin-menu.titulo'))
@section('heading', __('admin-menu.titulo'))
@section('subheading', __('admin-menu.subtitulo'))

@section('content')
    {{--
        El menú del portal.

        Los grupos van plegados. No es por ahorrar espacio: son ciento
        veintiocho entradas, sesenta y seis de ellas del mismo grupo, y abiertas
        de golpe no se distingue dónde acaba una sección y empieza la siguiente.
        Es el mismo motivo por el que se pliega el índice de Transparencia.

        Con <details> y no con un desplegable a mano: funciona sin JavaScript,
        el teclado lo abre con Enter sin programar nada y el navegador anuncia
        solo si está abierto o cerrado.
    --}}
    @if ($sinEditar)
        <div class="mb-8 rounded-[4px] border border-line border-l-4 border-l-rule-accent bg-card px-5 py-4">
            <h2 class="m-0 font-display text-15 font-bold text-heading">
                {{ __('admin-menu.sin_editar.titulo') }}
            </h2>

            <p class="m-0 mt-2 max-w-[70ch] text-13-5 leading-[1.6] text-body">
                {{ __('admin-menu.sin_editar.texto') }}
            </p>

            <form method="POST" action="{{ route('admin.menu.adopt') }}" class="mt-4">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-full border-0 bg-azure px-5 py-[9px]
                               font-display text-13-5 font-bold text-on-accent transition-colors hover:bg-azure-dark">
                    {{ __('admin-menu.sin_editar.boton') }}
                </button>
            </form>
        </div>
    @endif

    @foreach (MenuItem::AREAS as $area)
        @php $raices = $areas[$area]; @endphp

        <section @class(['mb-10' => ! $loop->last]) aria-labelledby="huv-area-{{ $area }}">
            <h2 id="huv-area-{{ $area }}" class="m-0 font-display text-16-5 font-bold text-heading">
                {{ __('admin-menu.area.'.$area.'.titulo') }}
            </h2>

            <p class="m-0 mt-1 mb-4 max-w-[70ch] text-13-5 leading-[1.6] text-muted">
                {{ __('admin-menu.area.'.$area.'.texto') }}
            </p>

            {{-- El tope de la barra es real y se ve al primer vistazo: a la
                 séptima sección la cabecera se parte en dos renglones y da un
                 salto al cambiar de página. Se avisa, no se impide: puede haber
                 un motivo, y quien edita el menú del hospital sabrá más que
                 nosotros. --}}
            @if ($area === MenuItem::AREA_BAR && $raices->count() > MenuItem::MAX_BAR_ITEMS)
                <p class="mb-4 rounded-[3px] border border-line border-l-4 border-l-warning bg-card px-4 py-3
                          text-13-5 text-body">
                    {{ __('admin-menu.area.bar.tope', [
                        'actuales' => $raices->count(),
                        'maximo' => MenuItem::MAX_BAR_ITEMS,
                    ]) }}
                </p>
            @endif

            @if ($raices->isEmpty())
                <p class="rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-8
                          text-center text-14 text-muted">
                    {{ __('admin-menu.sin_editar.titulo') }}
                </p>
            @else
                <div class="flex flex-col gap-3">
                    @foreach ($raices as $item)
                        <details id="huv-menu-{{ $item->id }}"
                                 class="overflow-hidden rounded-[3px] border border-stroke bg-card">
                            <summary class="flex cursor-pointer list-none items-center gap-3 border-b border-stroke
                                            px-4 py-3 hover:bg-tint [&::-webkit-details-marker]:hidden">
                                <svg class="size-4 shrink-0 text-muted transition-transform [details[open]_&]:rotate-180"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>

                                <span class="flex-1 font-display text-15 font-bold text-heading
                                             {{ $item->is_active ? '' : 'opacity-60' }}">
                                    {{ $item->label }}
                                </span>

                                <span class="text-12-5 text-faint">
                                    {{ trans_choice('admin-menu.fila.entradas', $item->children->count(), [
                                        'count' => $item->children->count(),
                                    ]) }}
                                </span>
                            </summary>

                            {{-- La propia sección: sus datos y sus acciones. --}}
                            @include('admin.menu.partials.fila', ['item' => $item])

                            @if ($item->children->isNotEmpty())
                                <div class="flex flex-col gap-px border-t border-rule bg-rule pl-6">
                                    @foreach ($item->children as $hijo)
                                        <div class="bg-card">
                                            @include('admin.menu.partials.fila', ['item' => $hijo])
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="border-t border-rule px-4 py-3">
                                <a href="{{ route('admin.menu.create', ['padre' => $item->id]) }}"
                                   class="inline-flex items-center gap-2 text-13-5 font-semibold text-link
                                          underline underline-offset-4">
                                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                                        <path d="M12 5v14M5 12h14" />
                                    </svg>
                                    {{ __('admin-menu.accion.agregar_entrada') }}
                                </a>
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif

            @unless ($sinEditar)
                <a href="{{ route('admin.menu.create', ['area' => $area]) }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-full border border-rule-accent bg-card
                          px-5 py-[9px] font-display text-14 font-semibold text-link no-underline
                          hover:bg-tint hover:no-underline">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    {{ __('admin-menu.accion.agregar_grupo') }}
                </a>
            @endunless
        </section>
    @endforeach
@endsection
