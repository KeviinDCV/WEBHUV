@php
    use App\Models\MenuItem;

    $backUrl = route('admin.menu.index');

    $esNueva = ! $item->exists;
    $esGrupo = $padre === null;
    $enMega = $item->area === MenuItem::AREA_MEGA;

    // Qué destino viene marcado: el que ya tenga, o «interno» para una entrada
    // nueva dentro de un grupo, que es el caso corriente.
    $destino = old('destino', match (true) {
        filled($item->url) => 'externo',
        filled($item->path) => 'interno',
        $item->exists || $esGrupo => 'ninguno',
        default => 'interno',
    });

    $titulo = $esNueva
        ? ($padre ? __('admin-menu.form.nueva_en', ['grupo' => $padre->label]) : __('admin-menu.form.nueva'))
        : __('admin-menu.form.editar', ['rotulo' => $item->label]);
@endphp

@extends('layouts.admin')

@section('title', $titulo)
@section('heading', $titulo)

@section('content')
    {{--
        Alta y edición de una entrada del menú.

        Los campos del destino se muestran y se ocultan con Alpine, pero sin él
        salen todos: es preferible un formulario con un campo de más que uno en
        el que no se pueda escribir. Quien manda es el botón elegido, y el
        controlador vacía el que no toque.
    --}}
    <form method="POST"
          action="{{ $esNueva ? route('admin.menu.store') : route('admin.menu.update', $item) }}"
          x-data="{ destino: @js($destino) }"
          class="max-w-[640px]">
        @csrf

        @unless ($esNueva)
            @method('PUT')
        @endunless

        @if ($esNueva)
            <input type="hidden" name="area" value="{{ $item->area }}">
            @if ($padre)
                <input type="hidden" name="padre" value="{{ $padre->id }}">
            @endif
        @endif

        {{-- ---------------- Rótulo ---------------- --}}
        <div class="mb-8">
            <label for="label" class="text-13-5 font-semibold text-heading">
                {{ __('admin-menu.campo.rotulo') }}
            </label>
            <input id="label" name="label" type="text" maxlength="120" required
                   value="{{ old('label', $item->label) }}"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
            <p class="m-0 mt-1 text-12-5 text-faint">{{ __('admin-menu.campo.rotulo_ayuda') }}</p>
        </div>

        {{-- ---------------- Destino ---------------- --}}
        <fieldset class="mb-8 border-0 p-0">
            <legend class="mb-2 p-0 text-13-5 font-semibold text-heading">
                {{ __('admin-menu.destino.titulo') }}
            </legend>

            <div class="flex flex-col gap-4">
                {{-- «A ningún sitio» solo se ofrece a las secciones: una entrada
                     dentro de un grupo que no lleve a ninguna parte sería un
                     rótulo muerto en medio del desplegable. --}}
                @foreach ($esGrupo ? ['interno', 'externo', 'ninguno'] : ['interno', 'externo'] as $opcion)
                    <div>
                        <label class="flex items-start gap-2 text-14 text-ink">
                            <input type="radio" name="destino" value="{{ $opcion }}"
                                   x-model="destino"
                                   @checked($destino === $opcion)
                                   class="mt-[3px] shrink-0">
                            <span>
                                {{ __('admin-menu.destino.'.$opcion) }}
                                <span class="mt-[2px] block text-12-5 text-faint">
                                    {{ __('admin-menu.destino.'.$opcion.'_ayuda') }}
                                </span>
                            </span>
                        </label>

                        @if ($opcion === 'interno')
                            <div x-show="destino === 'interno'" class="mt-2 pl-6">
                                <label for="path" class="sr-only">{{ __('admin-menu.campo.ruta') }}</label>
                                <input id="path" name="path" type="text" maxlength="255"
                                       placeholder="/tema/noticias"
                                       value="{{ old('path', $item->path) }}"
                                       class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                            </div>
                        @elseif ($opcion === 'externo')
                            <div x-show="destino === 'externo'" class="mt-2 pl-6">
                                <label for="url" class="sr-only">{{ __('admin-menu.campo.direccion') }}</label>
                                <input id="url" name="url" type="url" maxlength="255"
                                       placeholder="https://citas.huv.gov.co/login"
                                       value="{{ old('url', $item->url) }}"
                                       class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </fieldset>

        {{-- ---------------- Ajustes de presentación ---------------- --}}
        @if ($esGrupo && ! $enMega)
            <div class="mb-8">
                <label class="flex items-start gap-2 text-14 text-ink">
                    <input type="hidden" name="narrow" value="0">
                    <input type="checkbox" name="narrow" value="1"
                           @checked(old('narrow', $item->narrow))
                           class="mt-[3px] shrink-0">
                    <span>
                        {{ __('admin-menu.campo.estrecho') }}
                        <span class="mt-[2px] block text-12-5 text-faint">
                            {{ __('admin-menu.campo.estrecho_ayuda') }}
                        </span>
                    </span>
                </label>
            </div>
        @endif

        @if ($esGrupo && $enMega)
            <div class="mb-8 max-w-[240px]">
                <label for="columns" class="text-13-5 font-semibold text-heading">
                    {{ __('admin-menu.campo.columnas') }}
                </label>
                <select id="columns" name="columns"
                        class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
                    @foreach ([2, 3] as $n)
                        <option value="{{ $n }}" @selected((int) old('columns', $item->columns ?? 2) === $n)>{{ $n }}</option>
                    @endforeach
                </select>
                <p class="m-0 mt-1 text-12-5 text-faint">{{ __('admin-menu.campo.columnas_ayuda') }}</p>
            </div>
        @endif

        {{-- ---------------- Guardar ---------------- --}}
        <div class="flex flex-wrap items-center gap-5">
            <button type="submit"
                    class="rounded-full border-0 bg-azure px-6 py-[10px] font-display text-14 font-bold
                           text-on-accent transition-colors hover:bg-azure-dark">
                {{ __('admin-menu.form.guardar') }}
            </button>

            <a href="{{ route('admin.menu.index') }}" class="text-13-5 font-semibold text-link underline underline-offset-4">
                {{ __('admin-menu.form.cancelar') }}
            </a>
        </div>
    </form>
@endsection
