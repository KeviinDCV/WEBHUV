@php
    use App\Support\LegacyLink;

    $entity = config('huv.entity');
@endphp

<section aria-labelledby="huv-entidad" class="bg-page">
    <x-container class="grid grid-cols-1 items-start gap-10 py-14 lg:grid-cols-[1.05fr_0.95fr] lg:gap-14 lg:py-[62px]">

        <div class="flex flex-col gap-[18px]">
            <span class="font-display text-12-5 font-bold tracking-[0.16em] text-link uppercase">
                {{ $entity['eyebrow'] }}
            </span>

            <h2 id="huv-entidad"
                class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-balance text-heading lg:text-33">
                {{ $entity['title'] }}
            </h2>

            @foreach ($entity['paragraphs'] as $paragraph)
                <p class="m-0 text-15 leading-[1.72] text-pretty text-body lg:text-16">{{ $paragraph }}</p>
            @endforeach

            <div class="mt-[6px] grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($entity['cards'] as $card)
                    <div class="border-l-[3px] border-rule-accent py-1 pl-4">
                        <h3 class="m-0 mb-[7px] font-display text-13 font-bold tracking-[0.1em] text-heading uppercase">
                            {{ $card['title'] }}
                        </h3>
                        <p class="m-0 text-14-5 leading-[1.65] text-body">{{ $card['text'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-[10px] flex flex-wrap gap-3">
                @foreach ($entity['actions'] as $action)
                    @php($destino = LegacyLink::resolve($action))
                    <a href="{{ $destino['href'] }}"
                       @if ($destino['external']) target="_blank" rel="noopener noreferrer" @endif
                       class="inline-flex items-center rounded-[3px] px-6 py-[13px] font-display text-13-5
                              font-semibold no-underline transition-colors hover:no-underline
                              {{ $action['variant'] === 'primary'
                                  ? 'bg-navy text-on-brand hover:bg-navy-dark hover:text-on-brand'
                                  : 'border border-stroke-strong text-heading hover:border-rule-brand hover:bg-tint hover:text-heading' }}">
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="flex flex-col gap-5">
            <div class="relative h-[240px] lg:h-[300px]">
                <x-image-slot :src="$entity['image']"
                              alt="Fachada del Hospital Universitario del Valle «Evaristo García» E.S.E."
                              :hint="$entity['image_hint']"
                              :radius="4" />
            </div>

            <dl class="grid grid-cols-3 gap-px border border-line bg-line">
                @foreach ($entity['stats'] as $stat)
                    <div class="flex flex-col gap-1 bg-card px-4 py-5">
                        <dt class="sr-only">{{ $stat['label'] }} {{ $stat['label_extra'] }}</dt>
                        <dd class="m-0 flex flex-col gap-1">
                            <span class="font-display text-24 leading-none font-extrabold text-heading lg:text-27">
                                {{ $stat['value'] }}
                            </span>
                            <span aria-hidden="true" class="text-12-5 leading-[1.4] text-muted">
                                {{ $stat['label'] }}<br>{{ $stat['label_extra'] }}
                            </span>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </x-container>
</section>
