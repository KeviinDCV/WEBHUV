@props(['item'])

{{--
    Fila de un tema que se publica en lista y no en tarjetas.

    Hay dos formas, y no las decide el tema sino el contenido, igual que en el
    portal:

    · Un documento —«Normatividad»— lleva el icono con su peso a la izquierda,
      la categoría encima del título y, debajo, las dos fechas: cuándo se
      publicó y cuándo se expidió. Esto último es lo que se consulta de una
      norma, así que va escrito y no en relativo.

    · Cualquier otra cosa —los enlaces de «Contrataciones»— lleva solo la fecha
      arriba y el texto debajo: son cientos de registros de contratación y lo
      que se lee es el código, el contratista y el objeto.
--}}
@if ($item->isDocument())
    <div class="flex gap-4">
        {{-- El icono solo si hay algo que descargar: sin archivo prometería un
             PDF que no existe. --}}
        @if ($item->isDownloaded() || filled($item->source_url))
            <div class="flex shrink-0 flex-col items-center gap-1">
                <span class="flex size-[62px] flex-col items-center justify-center rounded-[3px] border-2
                             border-rule-accent bg-card">
                    <svg class="size-5 text-link" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 3v5h5" />
                        <path d="M19 8v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h8Z" />
                    </svg>
                    @if (filled($item->extension()))
                        <span class="mt-[2px] font-display text-10-5 font-bold tracking-[0.04em] text-link">
                            {{ $item->extension() }}
                        </span>
                    @endif
                </span>

                @if ($item->humanSize())
                    <span class="text-12 text-muted">{{ $item->humanSize() }}</span>
                @endif
            </div>
        @endif

        <div class="min-w-0 flex-1">
            @if ($item->categories->isNotEmpty())
                <p class="m-0 font-display text-11-5 font-semibold tracking-[0.06em] text-muted uppercase">
                    {{ $item->categories->pluck('name')->join(', ') }}
                </p>
            @endif

            <h3 class="m-0 mt-[2px] font-display text-15 leading-[1.4] font-bold">
                <a href="{{ $item->url() }}"
                   class="text-link underline decoration-1 underline-offset-4 hover:text-heading-hover">
                    {{ $item->title }}
                </a>
                <x-topic-item-actions :item="$item" class="ml-1 inline-flex align-[-5px]" />
            </h3>

            @if (filled($item->summary(280)))
                <p class="m-0 mt-1 text-13 leading-[1.5] whitespace-pre-line text-pretty text-muted">{{ $item->summary(280) }}</p>
            @endif

            <p class="m-0 mt-1 text-12 text-faint">
                @if ($item->date())
                    Publicación: <x-published-at :value="$item->date()" />
                @endif

                @if ($item->issued_at)
                    @if ($item->date())
                        <span aria-hidden="true">·</span>
                    @endif
                    Expedición:
                    {{-- Con el sello tal cual, como el portal: la hora es
                         siempre un relleno, pero es lo que enseña. --}}
                    <time datetime="{{ $item->issued_at->toIso8601String() }}">
                        {{ $item->issued_at->format('Y/m/d H:i:s') }}
                    </time>
                @endif
            </p>

            <x-topic-item-badges :item="$item" />
        </div>
    </div>
@else
    <p class="m-0 text-12 text-faint">
        @if ($item->date())
            <x-published-at :value="$item->date()" />
        @endif
    </p>

    <h3 class="m-0 mt-1 font-display text-15 leading-[1.4] font-bold">
        <a href="{{ $item->url() }}"
           class="text-link underline decoration-1 underline-offset-4 hover:text-heading-hover">
            {{ $item->title }}
        </a>
        <x-topic-item-actions :item="$item" class="ml-1 inline-flex align-[-5px]" />
    </h3>

    @if (filled($item->summary(280)))
        <p class="m-0 mt-1 text-13 leading-[1.5] whitespace-pre-line text-pretty text-muted">{{ $item->summary(280) }}</p>
    @endif

    <x-topic-item-badges :item="$item" />
@endif
