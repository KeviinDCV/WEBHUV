@props(['item'])

{{--
    Fila de un tema que se publica en lista y no en tarjetas.

    Hay dos formas, y no las decide el tema sino el contenido, igual que en el
    portal:

    · Un documento —«Normatividad»— lleva el icono con su peso a la izquierda,
      la categoría encima del título y, debajo, las dos fechas: cuándo se
      publicó y cuándo se expidió. Esto último es lo que se consulta de una
      norma, así que va escrito y no en relativo.

    · Cualquier otra cosa —los enlaces de «Contrataciones»— lleva solo la fecha
      arriba y el texto debajo: son cientos de registros de contratación y lo
      que se lee es el código, el contratista y el objeto.
--}}
@if ($item->isProcedure())
    {{--
        Trámite: nombre, para qué sirve, y a la derecha lo que hay que saber
        antes de venir al hospital —si es presencial o en línea, si cuesta y
        cuánto tarda—. La ficha completa vive en gov.co, así que el título y el
        «Ver más» llevan allí.
    --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 lg:max-w-[62ch] lg:flex-1">
            <h3 class="m-0 font-display text-15 leading-[1.4] font-bold">
                <a href="{{ $item->url() }}"
                   @if (filled($item->source_url)) target="_blank" rel="noopener noreferrer" @endif
                   class="text-link underline decoration-1 underline-offset-4 hover:text-heading-hover">
                    {{ $item->title }}
                    @if (filled($item->source_url))
                        <span class="sr-only">(se abre en una pestaña nueva)</span>
                    @endif
                </a>
                <x-topic-item-actions :item="$item" class="ml-1 inline-flex align-[-5px]" />
            </h3>

            @if (filled($item->summary(280)))
                <p class="m-0 mt-1 text-13-5 leading-[1.55] whitespace-pre-line text-pretty text-body">{{ $item->summary(280) }}</p>
            @endif

            {{-- «Ver más» además del título, como en el portal: el resumen se
                 corta a media frase y este es el enlace que se busca al llegar
                 ahí. Lleva al mismo sitio, así que no se anuncia dos veces a un
                 lector de pantalla: el nombre del trámite va en el propio
                 enlace. --}}
            @if (filled($item->source_url))
                <p class="m-0 mt-1">
                    <a href="{{ $item->url() }}" target="_blank" rel="noopener noreferrer"
                       class="text-13 text-link underline decoration-1 underline-offset-4">
                        Ver más<span class="sr-only"> sobre {{ $item->title }} (se abre en una pestaña nueva)</span>
                    </a>
                </p>
            @endif

            @if ($item->date())
                <p class="m-0 mt-2 text-12 text-faint">
                    Última modificación: <x-published-at :value="$item->date()" class="lowercase" />
                </p>
            @endif

            <x-topic-item-badges :item="$item" />
        </div>

        {{-- Los tres datos, con el mismo icono para el mismo dato en todas las
             filas: se leen en vertical, comparando trámite con trámite. --}}
        <dl class="m-0 flex shrink-0 flex-col gap-[6px] text-13 text-body lg:w-[230px]">
            @php
                $datos = [
                    ['dt' => 'Modalidad', 'dd' => $item->procedureType(), 'icono' => 'persona'],
                    ['dt' => 'Costo', 'dd' => $item->procedureCost(), 'icono' => 'moneda'],
                    ['dt' => 'Duración', 'dd' => $item->procedure_time
                        ? 'Duración '.$item->procedure_time
                        : null, 'icono' => 'reloj'],
                ];
            @endphp

            @foreach ($datos as $dato)
                @if (filled($dato['dd']))
                    <div class="flex items-start gap-2">
                        <dt class="sr-only">{{ $dato['dt'] }}</dt>
                        <dd class="m-0 flex items-start gap-2">
                            <svg class="mt-[2px] size-4 shrink-0 text-link" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                 stroke-linejoin="round" aria-hidden="true">
                                @if ($dato['icono'] === 'persona')
                                    <circle cx="12" cy="8" r="3.6" />
                                    <path d="M5 20a7 7 0 0 1 14 0" />
                                @elseif ($dato['icono'] === 'moneda')
                                    <circle cx="12" cy="12" r="8.5" />
                                    <path d="M12 7.5v9M14.6 9.8a2.6 2.6 0 0 0-5.2.3c0 2.6 5.2 1.3 5.2 3.9a2.6 2.6 0 0 1-5.2.3" />
                                @else
                                    <circle cx="12" cy="12" r="8.5" />
                                    <path d="M12 7v5.2l3.2 2" />
                                @endif
                            </svg>
                            <span>{{ $dato['dd'] }}</span>
                        </dd>
                    </div>
                @endif
            @endforeach
        </dl>
    </div>
@elseif ($item->isDocument())
    <div class="flex gap-4">
        {{-- El icono solo si hay algo que descargar: sin archivo prometería un
             PDF que no existe. --}}
        @if ($item->isDownloaded() || filled($item->source_url))
            <div class="flex shrink-0 flex-col items-center gap-1">
                <span class="flex size-[62px] flex-col items-center justify-center rounded-[3px] border-2
                             border-rule-accent bg-card">
                    <svg class="size-5 text-link" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 3v5h5" />
                        <path d="M19 8v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h8Z" />
                    </svg>
                    @if (filled($item->extension()))
                        <span class="mt-[2px] font-display text-10-5 font-bold tracking-[0.04em] text-link">
                            {{ $item->extension() }}
                        </span>
                    @endif
                </span>

                @if ($item->humanSize())
                    <span class="text-12 text-muted">{{ $item->humanSize() }}</span>
                @endif
            </div>
        @endif

        <div class="min-w-0 flex-1">
            @if ($item->categories->isNotEmpty())
                <p class="m-0 font-display text-11-5 font-semibold tracking-[0.06em] text-muted uppercase">
                    {{ $item->categories->pluck('name')->join(', ') }}
                </p>
            @endif

            <h3 class="m-0 mt-[2px] font-display text-15 leading-[1.4] font-bold">
                <a href="{{ $item->url() }}"
                   class="text-link underline decoration-1 underline-offset-4 hover:text-heading-hover">
                    {{ $item->title }}
                </a>
                <x-topic-item-actions :item="$item" class="ml-1 inline-flex align-[-5px]" />
            </h3>

            @if (filled($item->summary(280)))
                <p class="m-0 mt-1 text-13 leading-[1.5] whitespace-pre-line text-pretty text-muted">{{ $item->summary(280) }}</p>
            @endif

            <p class="m-0 mt-1 text-12 text-faint">
                @if ($item->date())
                    Publicación: <x-published-at :value="$item->date()" />
                @endif

                @if ($item->issued_at)
                    @if ($item->date())
                        <span aria-hidden="true">·</span>
                    @endif
                    Expedición:
                    {{-- Con el sello tal cual, como el portal: la hora es
                         siempre un relleno, pero es lo que enseña. --}}
                    <time datetime="{{ $item->issued_at->toIso8601String() }}">
                        {{ $item->issued_at->format('Y/m/d H:i:s') }}
                    </time>
                @endif
            </p>

            <x-topic-item-badges :item="$item" />
        </div>
    </div>
@else
    <p class="m-0 text-12 text-faint">
        @if ($item->date())
            <x-published-at :value="$item->date()" />
        @endif
    </p>

    <h3 class="m-0 mt-1 font-display text-15 leading-[1.4] font-bold">
        <a href="{{ $item->url() }}"
           class="text-link underline decoration-1 underline-offset-4 hover:text-heading-hover">
            {{ $item->title }}
        </a>
        <x-topic-item-actions :item="$item" class="ml-1 inline-flex align-[-5px]" />
    </h3>

    @if (filled($item->summary(280)))
        <p class="m-0 mt-1 text-13 leading-[1.5] whitespace-pre-line text-pretty text-muted">{{ $item->summary(280) }}</p>
    @endif

    <x-topic-item-badges :item="$item" />
@endif
