@php $links = config('huv.quick_links'); @endphp

<section aria-labelledby="huv-accesos" class="bg-page">
    <x-container class="py-12 lg:py-16">
        <x-edit-chip section="accesos" label="accesos directos" />

        <h2 id="huv-accesos" class="sr-only">Accesos directos a trámites y canales de atención</h2>

        <ul class="grid grid-cols-2 gap-x-4 gap-y-9 sm:grid-cols-3 md:grid-cols-5">
            @foreach ($links as $link)
                <li>
                    <a href="{{ $link['url'] }}"
                       class="group flex h-full flex-col items-center gap-3 rounded-[4px] px-2 py-3 text-center
                              no-underline transition-colors hover:bg-tint hover:no-underline">
                        <x-quick-icon :name="$link['icon']"
                                      class="size-7 text-link transition-transform group-hover:-translate-y-[2px]" />
                        <span class="font-display text-12-5 leading-[1.35] font-bold text-heading">
                            {{ $link['label'] }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-container>
</section>
