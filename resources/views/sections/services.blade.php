@php
    use App\Support\ConfigLabel;

    $services = config('huv.services');
@endphp

<section aria-labelledby="huv-servicios" class="bg-page">
    <x-container class="py-14 lg:py-[58px]">
        <h2 id="huv-servicios"
            class="m-0 mb-[6px] font-display text-22 font-bold tracking-[-0.01em] text-heading lg:text-26">
            {{ ConfigLabel::of($services, 'title', 'titulo') }}
        </h2>
        <p class="m-0 mb-7 text-15 text-muted">{{ ConfigLabel::of($services, 'subtitle', 'subtitulo') }}</p>

        <ul class="flex flex-wrap gap-[10px]">
            @foreach ($services['items'] as $posicion => $service)
                <li class="rounded-[3px] border border-tint-line bg-tint px-[15px] py-[9px] text-13-5 text-heading">
                    {{ ConfigLabel::item($services, 'items', $posicion, $service) }}
                </li>
            @endforeach
        </ul>
    </x-container>
</section>
