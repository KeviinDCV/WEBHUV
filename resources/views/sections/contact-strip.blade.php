@php
    use App\Support\ConfigLabel;

    $strip = config('huv.contact_strip');
@endphp

<section aria-label="{{ __('portada.lineas.titulo') }}" class="bg-navy text-on-brand">
    <x-container class="grid grid-cols-1 items-center gap-7 py-[30px] sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($strip as $line)
            <div class="flex flex-col gap-[3px]">
                <span class="text-11-5 font-semibold tracking-[0.12em] text-on-brand-label uppercase">
                    {{ ConfigLabel::of($line, 'label', 'rotulo') }}
                </span>

                @if (! empty($line['tel']))
                    <a href="tel:{{ $line['tel'] }}"
                       class="font-display text-21 font-bold text-on-brand no-underline hover:text-on-brand hover:underline">
                        {{ ConfigLabel::of($line, 'value', 'valor') }}
                    </a>
                @elseif (! empty($line['value_extra']))
                    <span class="text-14-5 leading-[1.4] font-medium">
                        {{ ConfigLabel::of($line, 'value', 'valor') }}<br>{{ $line['value_extra'] }}
                    </span>
                @else
                    <span class="font-display text-21 font-bold">{{ ConfigLabel::of($line, 'value', 'valor') }}</span>
                @endif
            </div>
        @endforeach
    </x-container>
</section>
