@props(['datos'])

{{--
    Un bloque schema.org.

    Se emiten varios por página —la organización, la ficha, el rastro de
    migas— y eso es correcto: el formato admite tantos como haga falta, y
    partirlos es más legible que armar un grafo único.

    Las banderas de json_encode no son decorativas: JSON_HEX_TAG evita que un
    título con «</script>» cierre la etiqueta antes de tiempo, y JSON_HEX_AMP
    hace lo propio con las entidades. El contenido viene de la base y puede
    traer cualquier cosa.
--}}
@if (filled($datos))
    <script type="application/ld+json">
        {!! json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
@endif
