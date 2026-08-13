@props(['images'])

@if ($images->isNotEmpty())
    {{--
        Galería de fotos de un contenido.

        Vive aparte porque la necesitan dos fichas con ramas distintas: la de un
        artículo, que va por `partials/ficha-medios`, y la de un documento o una
        convocatoria, que se pinta a mano en `topics/item`. Sin compartirla, un
        documento con una foto adjunta —el origen las mezcla con los PDF y las
        marca con `isImage`— se quedaba sin sitio donde enseñarla y la foto
        desaparecía de la ficha.

        `object-contain` y no `cover`: aquí no siempre hay fotografías. Las siete
        láminas de «Valores y Principios Corporativos» son carteles verticales
        llenos de texto, y recortarlos a 4/3 se comía justo lo que hay que leer.
        Encajadas dentro del marco se ven enteras, y el fondo de color mantiene
        la rejilla pareja.

        Cada una enlaza a su archivo porque en miniatura no se leen: es la forma
        más simple de dar la lámina a tamaño completo sin montar un visor por
        encima.
    --}}
    <div class="mt-8">
        <h2 class="m-0 mb-3 font-display text-17 font-bold text-heading">Galería</h2>

        <ul class="grid grid-cols-2 gap-4 md:grid-cols-3">
            @foreach ($images as $image)
                <li>
                    <figure class="m-0">
                        <a href="{{ $image->fileUrl() }}" class="block">
                            <img src="{{ $image->fileUrl() }}"
                                 alt="{{ $image->alt ?: 'Ampliar la imagen '.$loop->iteration.' de '.$images->count() }}"
                                 loading="lazy" decoding="async"
                                 class="aspect-[4/3] w-full rounded-[3px] border border-line bg-tint object-contain">
                        </a>
                        @if (filled($image->alt))
                            <figcaption class="mt-1 text-12 text-muted">{{ $image->alt }}</figcaption>
                        @endif
                    </figure>
                </li>
            @endforeach
        </ul>
    </div>
@endif
