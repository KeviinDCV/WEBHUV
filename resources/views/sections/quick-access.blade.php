@php $quick = config('huv.quick_access'); @endphp

<section aria-labelledby="huv-accesos" class="border-b border-line-pale bg-surface">
    <x-container class="pt-11 pb-12">
        <h2 id="huv-accesos"
            class="m-0 mb-[6px] font-display text-22 font-bold tracking-[-0.01em] text-heading lg:text-26">
            {{ $quick['title'] }}
        </h2>
        <p class="m-0 mb-7 text-15 text-muted">{{ $quick['subtitle'] }}</p>

        <ul class="grid grid-cols-1 gap-[18px] sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($quick['items'] as $item)
                <li class="flex">
                    <a href="{{ $item['url'] }}"
                       class="group flex w-full flex-col gap-[9px] rounded-[4px] border border-line border-t-4
                              border-t-rule-accent bg-card px-5 pt-[22px] pb-6 no-underline transition
                              hover:border-rule-brand hover:border-t-rule-accent hover:no-underline
                              hover:shadow-[0_10px_26px_rgba(23,32,64,0.1)]">
                        <h3 class="m-0 font-display text-16 font-bold text-heading">{{ $item['title'] }}</h3>
                        <p class="m-0 text-13-5 leading-[1.55] text-muted">{{ $item['text'] }}</p>
                        <span class="mt-auto flex items-center gap-1 pt-2 text-12-5 font-bold text-link">
                            {{ $item['cta'] }}
                            <span aria-hidden="true" class="transition-transform group-hover:translate-x-[3px]">→</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-container>
</section>
