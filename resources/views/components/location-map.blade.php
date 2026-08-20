@props(['map'])

{{--
    Bloque de mapa.

    En el portal esto es un «bloque» que se cuelga de un tema y se edita desde
    el panel; el único que existe en todo el sitio es el de Directorio
    institucional, así que aquí vive en configuración. Va a todo el ancho de la
    pantalla, como allí.

    Dentro del contenedor está la alternativa en texto, que es lo que se ve
    mientras Leaflet llega y lo que se queda si no llega nunca: sin JavaScript,
    con la red caída o con un bloqueador de por medio, la dirección y el enlace
    al mapa siguen ahí. El componente la retira al tomar el mando.
--}}
{{--
    `isolate` no es decorativo. Leaflet coloca sus capas con z-index propios
    —hasta 1000 en los controles—, y ni esta sección ni su contenedor crean
    contexto de apilamiento por sí solos: esos números competirían en el
    contexto raíz contra la cabecera fija, el rail de accesibilidad y el cajón
    de navegación, y los botones de zoom acabarían pintados por encima. Con
    `isolate` la escala de Leaflet se queda dentro de su caja.
--}}
@php
    // Los rótulos del globo del marcador se calculan aquí y no dentro del
    // atributo: el JavaScript del mapa no sabe en qué idioma está la página.
    $mapa = $map + ['textos' => [
        'comoLlegar' => __('componentes.mapa.como_llegar'),
        'pestanaNueva' => __('componentes.mapa.nueva_pestana'),
        'cerrar' => __('componentes.mapa.cerrar'),
        'acercar' => __('componentes.mapa.acercar'),
        'alejar' => __('componentes.mapa.alejar'),
        // Con :lugar y :direccion sin resolver: el JavaScript los sustituye
        // cuando sabe qué marcador está pintando.
        'lienzo' => __('componentes.mapa.lienzo'),
        'lienzoConDireccion' => __('componentes.mapa.lienzo_con_direccion'),
    ]];
@endphp

<section aria-labelledby="huv-mapa-titulo" x-data='huvMap(@json($mapa))' class="relative isolate mt-10">
    <h2 id="huv-mapa-titulo" class="sr-only">{{ App\Support\ConfigLabel::of($map, 'title', 'titulo') }}</h2>

    {{-- Aviso de la rueda: aparece solo si se intenta acercar sin haber pulsado
         dentro. `pointer-events-none` para no robarle ese clic al mapa. --}}
    <div x-show="hint" x-cloak x-transition.opacity
         class="pointer-events-none absolute inset-0 z-[1000] flex items-center justify-center">
        <p class="m-0 rounded-full border border-line bg-navy px-5 py-2 text-13-5 font-semibold text-on-brand">
            {{ __('componentes.mapa.aviso_rueda') }}
        </p>
    </div>

    <div x-ref="canvas" class="h-[320px] w-full bg-tint md:h-[500px]">
        <div class="flex h-full flex-col items-center justify-center gap-2 px-6 text-center">
            <p class="m-0 font-display text-15 font-bold text-heading">{{ $map['label'] }}</p>
            <p class="m-0 text-14 text-muted">{{ $map['address'] }}</p>
            <a href="https://www.openstreetmap.org/?mlat={{ $map['latitude'] }}&amp;mlon={{ $map['longitude'] }}#map={{ $map['zoom'] }}/{{ $map['latitude'] }}/{{ $map['longitude'] }}"
               target="_blank" rel="noopener noreferrer" class="text-14 font-semibold text-link">
                {{ __('componentes.mapa.ver_en_openstreetmap') }}
                <span class="sr-only">{{ __('componentes.enlace.pestana_nueva') }}</span>
            </a>
        </div>
    </div>
</section>
