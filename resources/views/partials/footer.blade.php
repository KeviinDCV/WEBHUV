@php
    $institution = config('huv.institution');
    $footer = config('huv.footer');
    $legalTime = $footer['legal_time'];

    /*
     | «Última modificación»: se toma de la fecha real de cambio del archivo de
     | contenido, así el dato se mantiene solo mientras no exista un gestor de
     | contenidos. Cuando lo haya, basta con sustituir esta línea por el campo
     | updated_at del registro correspondiente.
    */
    $lastModified = \Illuminate\Support\Carbon::createFromTimestamp(
        filemtime(config_path('huv.php')),
        config('app.timezone')
    );
@endphp

<footer class="border-t-[3px] border-rule-brand bg-surface">
    <x-container class="py-12">

        <div class="grid grid-cols-1 gap-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:gap-14">

            {{-- Datos institucionales --}}
            <div>
                <h2 class="m-0 mb-4 font-display text-16-5 font-bold text-heading">
                    {{ $institution['name_plain'] }}
                </h2>

                <address class="not-italic">
                    <dl class="flex flex-col gap-[7px] text-14 leading-[1.55] text-body">
                        @foreach ($footer['contact'] as $row)
                            <div class="flex flex-wrap gap-x-[6px]">
                                <dt class="shrink-0">{{ $row['label'] }}:</dt>
                                <dd class="m-0 min-w-0">
                                    @if (! empty($row['tel']))
                                        <a href="tel:{{ $row['tel'] }}"
                                           class="text-body underline underline-offset-2 hover:text-heading">{{ $row['value'] }}</a>
                                    @elseif (! empty($row['mailto']))
                                        <a href="mailto:{{ $row['mailto'] }}"
                                           class="break-all text-body underline underline-offset-2 hover:text-heading">{{ $row['value'] }}</a>
                                    @else
                                        {{ $row['value'] }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </address>
            </div>

            {{-- Marca, hora legal y última modificación --}}
            <div class="flex flex-col gap-5 lg:w-[420px] lg:items-end lg:border-l lg:border-line lg:pl-14">
                <img src="{{ asset('img/logo-huv.png') }}"
                     alt="{{ $institution['name'] }}"
                     width="620" height="175" loading="lazy" decoding="async"
                     class="block h-[52px] w-auto">

                @if ($legalTime['enabled'])
                    @include('partials.legal-time', ['legalTime' => $legalTime])
                @endif

                <p class="m-0 text-13-5 text-muted">
                    Última modificación
                    <time datetime="{{ $lastModified->toIso8601String() }}">
                        {{ \Illuminate\Support\Str::ucfirst($lastModified->diffForHumans()) }}
                    </time>
                </p>
            </div>
        </div>

        {{-- Redes sociales --}}
        <h2 id="huv-footer-redes" class="sr-only">Redes sociales del hospital</h2>
        <ul aria-labelledby="huv-footer-redes"
            class="mt-12 grid grid-cols-1 gap-x-14 gap-y-5 sm:grid-cols-2">
            @foreach ($footer['social'] as $account)
                <li>
                    <a href="{{ $account['url'] }}" target="_blank" rel="noopener noreferrer"
                       class="group inline-flex max-w-full items-center gap-[10px] text-14 text-body no-underline
                              hover:text-heading hover:no-underline">
                        <span class="flex size-[26px] shrink-0 items-center justify-center rounded-full
                                     bg-azure text-on-accent transition-colors group-hover:bg-azure-dark">
                            <x-social-icon :network="$account['network']" />
                        </span>
                        <span class="truncate group-hover:underline">{{ $account['handle'] }}</span>
                        <span class="sr-only">en {{ $account['name'] }} (se abre en una pestaña nueva)</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-container>

    {{-- Franja legal --}}
    <div class="border-t border-line">
        <x-container class="flex flex-wrap items-center gap-x-8 gap-y-3 py-5 text-13-5">
            <span class="text-muted">
                Última modificación
                <time datetime="{{ $lastModified->toIso8601String() }}">
                    {{ \Illuminate\Support\Str::ucfirst($lastModified->diffForHumans()) }}
                </time>
            </span>

            <nav aria-label="Enlaces legales y de servicio">
                <ul class="flex flex-wrap items-center gap-x-8 gap-y-3">
                    @foreach ($footer['legal_links'] as $link)
                        <li>
                            <a href="{{ $link['url'] }}"
                               class="font-medium text-heading underline underline-offset-4 hover:text-heading-hover">
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </x-container>
    </div>
</footer>
