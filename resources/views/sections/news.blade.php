@php $news = config('huv.news'); @endphp

<section id="noticias" aria-labelledby="huv-noticias"
         class="border-y border-line-pale bg-surface">
    <x-container class="pt-14 pb-15">

        <div class="mb-[30px] flex flex-wrap items-end justify-between gap-6">
            <div>
                <h2 id="huv-noticias"
                    class="m-0 mb-[6px] font-display text-22 font-bold tracking-[-0.01em] text-heading lg:text-26">
                    {{ $news['title'] }}
                </h2>
                <p class="m-0 text-15 text-muted">{{ $news['subtitle'] }}</p>
            </div>
            <a href="{{ $news['all_url'] }}"
               class="group shrink-0 font-display text-13-5 font-semibold text-link">
                Ver todas las noticias
                <span aria-hidden="true" class="inline-block transition-transform group-hover:translate-x-[3px]">→</span>
            </a>
        </div>

        <ul class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($news['items'] as $item)
                <li class="flex">
                    <article class="flex w-full flex-col overflow-hidden rounded-[4px] border border-line bg-card
                                    transition hover:shadow-[0_10px_26px_rgba(23,32,64,0.1)]">
                        <div class="relative h-[186px]">
                            <x-image-slot :src="$item['image']" :alt="$item['title']" :hint="$item['image_hint']" />
                        </div>

                        <div class="flex flex-1 flex-col gap-[10px] px-5 pt-5 pb-6">
                            <div class="flex flex-wrap items-center gap-[10px]">
                                <span class="bg-azure px-2 py-1 text-10-5 font-bold tracking-[0.1em] text-on-accent uppercase">
                                    {{ $item['category'] }}
                                </span>
                                @if ($item['datetime'])
                                    <time datetime="{{ $item['datetime'] }}" class="text-12 text-faint">{{ $item['date'] }}</time>
                                @else
                                    <span class="text-12 text-faint">{{ $item['date'] }}</span>
                                @endif
                            </div>

                            <h3 class="m-0 font-display text-17 leading-[1.35] font-bold text-balance text-heading">
                                {{-- El enlace envuelve todo el título: área de clic amplia y accesible. --}}
                                <a href="{{ $item['url'] }}" class="text-heading hover:text-heading-hover">{{ $item['title'] }}</a>
                            </h3>

                            <p class="m-0 text-14 leading-[1.6] text-pretty text-muted">{{ $item['excerpt'] }}</p>

                            <span aria-hidden="true" class="mt-auto pt-[6px] text-12-5 font-bold text-link">Leer más →</span>
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>
    </x-container>
</section>
