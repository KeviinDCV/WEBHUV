@php
    use App\Support\ConfigLabel;
    use App\Support\LegacyLink;

    $quick = config('huv.quick_access');
@endphp

<section aria-labelledby="huv-accesos" class="border-b border-line-pale bg-surface">
    <x-container class="pt-11 pb-12">
        <h2 id="huv-accesos"
            class="m-0 mb-[6px] font-display text-22 font-bold tracking-[-0.01em] text-heading lg:text-26">
            {{ ConfigLabel::of($quick, 'title', 'titulo') }}
        </h2>
        <p class="m-0 mb-7 text-15 text-muted">{{ ConfigLabel::of($quick, 'subtitle', 'subtitulo') }}</p>

        <ul class="grid grid-cols-1 gap-[18px] sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($quick['items'] as $item)
                {{-- Por LegacyLink, como los del menú: mientras la sección no
                     esté migrada el acceso lleva al portal anterior, y pasa a
                     este aplicativo solo. --}}
                @php($destino = LegacyLink::resolve($item))
                <li class="flex">
                    <a href="{{ $destino['href'] }}"
                       @if ($destino['external']) target="_blank" rel="noopener noreferrer" @endif
                       class="group flex w-full flex-col gap-[9px] rounded-[4px] border border-line border-t-4
                              border-t-rule-accent bg-card px-5 pt-[22px] pb-6 no-underline transition
                              hover:border-rule-brand hover:border-t-rule-accent hover:no-underline
                              hover:shadow-[0_10px_26px_rgba(23,32,64,0.1)]">
                        <h3 class="m-0 font-display text-16 font-bold text-heading">{{ ConfigLabel::of($item, 'title', 'titulo') }}</h3>
                        <p class="m-0 text-13-5 leading-[1.55] text-muted">{{ ConfigLabel::of($item, 'text', 'texto') }}</p>
                        <span class="mt-auto flex items-center gap-1 pt-2 text-12-5 font-bold text-link">
                            {{ ConfigLabel::of($item, 'cta', 'accion') }}
                            <span aria-hidden="true" class="transition-transform group-hover:translate-x-[3px]">→</span>
                            @if ($destino['external'])
                                <span class="sr-only">{{ __('portada.nueva_pestana') }}</span>
                            @endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-container>
</section>
