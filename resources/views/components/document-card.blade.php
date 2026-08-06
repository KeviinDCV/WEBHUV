@props(['document'])

{{--
    Ficha de un documento en el listado de un tema.

    El icono lleva la extensión escrita porque en un listado de decenas de PDF
    la forma del icono no distingue nada: lo que importa es si algo es un PDF o
    una hoja de cálculo, y eso se lee.
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
                {{ $document->extension() }}
            </span>
        </span>

        @if ($document->humanSize())
            <span class="text-12 text-muted">{{ $document->humanSize() }}</span>
        @endif
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-[6px]">
        @if ($document->date())
            <p class="m-0 text-12 text-faint">
                <x-published-at :value="$document->date()" />
            </p>
        @endif

        @if ($document->category)
            <p class="m-0 text-12-5 text-link">{{ $document->category->name }}</p>
        @endif

        <h3 class="m-0 font-display text-15 leading-[1.4] font-bold text-balance">
            <a href="{{ $document->url() }}"
               class="text-heading underline decoration-1 underline-offset-4 hover:text-heading-hover">
                {{ $document->title }}
            </a>
            {{-- El lápiz va pegado al título, como en el portal actual. --}}
            <x-document-actions :document="$document" class="ml-1 inline-flex align-[-5px]" />
        </h3>

        @if (filled($document->summary()))
            <p class="m-0 text-13-5 leading-[1.6] text-pretty text-muted">{{ $document->summary() }}</p>
        @endif

        <x-document-badges :document="$document" />
    </div>
</article>
