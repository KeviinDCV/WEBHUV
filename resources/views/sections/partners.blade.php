@php
    use App\Support\ConfigLabel;

    $partners = config('huv.partners');
@endphp

<section aria-labelledby="huv-entidades" class="border-t border-line-pale bg-page" x-data="huvLogoStrip">
    <x-container class="py-10 lg:py-12">

        <x-edit-chip section="entidades" :label="__('portada.chip.entidades')" />

        <h2 id="huv-entidades" class="sr-only">{{ ConfigLabel::of($partners, 'title') }}</h2>

        <div class="flex items-center gap-2">
            <button type="button" @click="scrollBy(-1)" :disabled="atStart" aria-label="{{ __('portada.entidades.anteriores') }}"
                    class="flex size-9 shrink-0 items-center justify-center rounded-full border border-stroke
                           bg-card text-heading transition-colors hover:bg-tint
                           disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-card">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 5-7 7 7 7" />
                </svg>
            </button>

            {{-- `tabindex="0"` porque un contenedor con scroll debe poder
                 recorrerse también con el teclado. --}}
            <ul x-ref="strip" tabindex="0" role="list"
                aria-label="{{ ConfigLabel::of($partners, 'title') }}"
                class="flex flex-1 snap-x snap-mandatory gap-8 overflow-x-auto scroll-smooth py-2
                       [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($partners['items'] as $entity)
                    <li class="w-[150px] shrink-0 snap-start sm:w-[170px]">
                        <a href="{{ $entity['url'] }}" target="_blank" rel="noopener noreferrer"
                           class="flex h-full flex-col items-center gap-2 rounded-[4px] px-2 py-3 text-center
                                  no-underline transition-opacity hover:opacity-80 hover:no-underline">
                            <span class="flex h-[52px] w-full items-center justify-center">
                                <span{!! App\Support\PortalLang::attribute() !!}>
                                    <x-image-slot :src="$entity['logo']" :alt="$entity['name']"
                                                  :hint="$entity['name']" class="!h-[52px] object-contain" />
                                </span>
                            </span>
                            {{-- El nombre de la entidad es un nombre propio en
                                 español: se declara su idioma. --}}
                            <x-texto-del-portal class="text-11-5 leading-[1.35] font-semibold text-muted">{{ $entity['name'] }}</x-texto-del-portal>
                        </a>
                    </li>
                @endforeach
            </ul>

            <button type="button" @click="scrollBy(1)" :disabled="atEnd" aria-label="{{ __('portada.entidades.siguientes') }}"
                    class="flex size-9 shrink-0 items-center justify-center rounded-full border border-stroke
                           bg-card text-heading transition-colors hover:bg-tint
                           disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-card">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m9 5 7 7-7 7" />
                </svg>
            </button>
        </div>
    </x-container>
</section>
