@php $transparency = config('huv.transparency'); @endphp

<section id="transparencia" aria-labelledby="huv-transparencia" class="bg-navy-deep text-on-brand">
    <x-container class="pt-14 pb-15 lg:pt-[58px] lg:pb-[62px]">
        <h2 id="huv-transparencia"
            class="m-0 mb-[6px] font-display text-22 font-bold tracking-[-0.01em] lg:text-26">
            {{ $transparency['title'] }}
        </h2>
        <p class="m-0 mb-[30px] text-15 text-on-brand-muted">{{ $transparency['subtitle'] }}</p>

        <ol class="grid grid-cols-1 gap-px bg-brand-hairline sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
            @foreach ($transparency['items'] as $index => $item)
                <li class="flex">
                    <a href="#"
                       class="flex w-full flex-col gap-2 bg-navy-deep px-[18px] pt-[22px] pb-6 text-on-brand
                              no-underline transition-colors hover:bg-navy-soft hover:text-on-brand hover:no-underline">
                        <span aria-hidden="true" class="font-display text-12 font-bold text-azure-pale">
                            {{ $index + 1 }}
                        </span>
                        <span class="font-display text-14-5 leading-[1.35] font-semibold">{{ $item }}</span>
                    </a>
                </li>
            @endforeach
        </ol>
    </x-container>
</section>
