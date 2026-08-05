@php
    $bulletins = config('huv.bulletins');
    $featured = $bulletins['featured'];
@endphp

<section aria-labelledby="huv-boletines" class="bg-navy-deep text-on-brand">
    <x-container class="py-12 lg:py-14">

        <x-edit-chip section="boletines" label="boletines" />

        <h2 id="huv-boletines"
            class="m-0 mb-8 font-display text-22 font-bold underline decoration-2 underline-offset-8 lg:text-26">
            {{ $bulletins['title'] }}
        </h2>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:gap-12">

            {{-- Documento destacado --}}
            <article class="flex flex-col gap-4">
                <a href="{{ $featured['url'] }}" tabindex="-1" aria-hidden="true"
                   class="block aspect-[4/3] overflow-hidden rounded-[3px] bg-white/10 sm:aspect-[3/2]">
                    <x-image-slot :src="$featured['document']" :alt="''"
                                  :hint="$featured['document_hint']" :bordered="false" />
                </a>

                <div class="flex flex-col gap-2">
                    <h3 class="m-0 font-display text-17 leading-[1.4] font-bold">
                        <a href="{{ $featured['url'] }}"
                           class="text-on-brand underline decoration-1 underline-offset-4 hover:text-on-brand">
                            {{ $featured['title'] }}
                        </a>
                    </h3>
                    <p class="m-0 text-14 leading-[1.6] text-pretty text-on-brand-muted">{{ $featured['excerpt'] }}</p>
                    <x-published-at :value="$featured['published_at']" class="text-12-5 text-on-brand-label" />
                </div>
            </article>

            {{-- Resto de publicaciones --}}
            <ul class="flex flex-col gap-6">
                @foreach ($bulletins['items'] as $item)
                    <li>
                        <article class="grid grid-cols-[110px_minmax(0,1fr)] gap-4 sm:grid-cols-[150px_minmax(0,1fr)]">
                            <a href="{{ $item['url'] }}" tabindex="-1" aria-hidden="true"
                               class="block aspect-[3/2] overflow-hidden rounded-[3px] bg-white/10">
                                <x-image-slot :src="$item['document']" :alt="''"
                                              :hint="$item['document_hint']" :bordered="false" />
                            </a>

                            <div class="flex min-w-0 flex-col gap-[6px]">
                                <h3 class="m-0 font-display text-14-5 leading-[1.4] font-semibold text-pretty">
                                    <a href="{{ $item['url'] }}"
                                       class="text-on-brand underline decoration-1 underline-offset-4 hover:text-on-brand">
                                        {{ $item['title'] }}
                                    </a>
                                </h3>
                                <p class="m-0 text-13-5 leading-[1.55] text-pretty text-on-brand-muted">
                                    {{ $item['excerpt'] }}
                                </p>
                                <x-published-at :value="$item['published_at']" class="text-12 text-on-brand-label" />
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="m-0 mt-9">
            <a href="{{ $bulletins['all_url'] }}"
               class="group inline-flex items-center gap-2 rounded-[3px] border border-on-brand/40 px-5 py-[10px]
                      font-display text-13-5 font-semibold text-on-brand no-underline
                      transition-colors hover:border-on-brand hover:bg-white/10 hover:text-on-brand hover:no-underline">
                Ver todos los boletines
                <span aria-hidden="true" class="transition-transform group-hover:translate-x-[3px]">→</span>
            </a>
        </p>
    </x-container>
</section>
