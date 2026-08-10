@props(['item'])

@if ($item->isDocument())
    {{--
        Documento: el icono lleva la extensión escrita porque en un listado de
        decenas de PDF la forma del icono no distingue nada.
    --}}
    <article class="flex w-full gap-4 overflow-hidden rounded-[4px] border border-line bg-card p-4
                    transition hover:shadow-[0_10px_26px_rgba(23,32,64,0.1)]">

        <div class="flex shrink-0 flex-col items-center gap-1">
            <span class="flex size-[62px] flex-col items-center justify-center rounded-[3px] border-2
                         border-rule-accent bg-card">
                <svg class="size-5 text-link" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 3v5h5" />
                    <path d="M19 8v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h8Z" />
                </svg>
                <span class="mt-[2px] font-display text-10-5 font-bold tracking-[0.04em] text-link">
                    {{ $item->extension() }}
                </span>
            </span>

            @if ($item->humanSize())
                <span class="text-12 text-muted">{{ $item->humanSize() }}</span>
            @endif
        </div>

        <div class="flex min-w-0 flex-1 flex-col gap-[6px]">
            <x-topic-item-meta :item="$item" />
        </div>
    </article>
@elseif ($item->isConvocation())
    {{--
        Convocatoria: tarjeta llena de color, como en el portal.

        Allí es un azul liso que la separa del resto de listados de un vistazo.
        Se usa el azul del tema y no el suyo a pelo, para que el modo de alto
        contraste lo siga cambiando: un hexadecimal escrito aquí se quedaría
        igual y dejaría la tarjeta ilegible con ese modo puesto.
    --}}
    <article class="flex w-full flex-col overflow-hidden rounded-[4px] bg-azure text-on-accent
                    transition hover:bg-azure-dark
                    hover:shadow-[0_10px_26px_rgba(23,32,64,0.18)]">
        <div class="flex flex-1 flex-col gap-[6px] p-5">
            <x-topic-item-meta :item="$item" tone="accent" />
        </div>
    </article>
@else
    {{-- Artículo: la imagen manda, arriba y a todo el ancho de la tarjeta. --}}
    <article class="flex w-full flex-col overflow-hidden rounded-[4px] border border-line bg-card
                    transition hover:shadow-[0_10px_26px_rgba(23,32,64,0.1)]">

        @if ($item->imageUrl())
            <a href="{{ $item->url() }}" tabindex="-1" aria-hidden="true" class="block">
                <img src="{{ $item->imageUrl() }}" alt=""
                     loading="lazy" decoding="async"
                     class="aspect-[16/9] w-full object-cover">
            </a>
        @endif

        <div class="flex flex-1 flex-col gap-[6px] p-5">
            <x-topic-item-meta :item="$item" />
        </div>
    </article>
@endif
