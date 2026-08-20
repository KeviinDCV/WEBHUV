@props(['tag' => 'span'])

@php
    use App\Support\PortalLang;
@endphp

{{--
    Envuelve texto que viene del portal —de la base de datos, o rótulos propios
    de la institución— y lo marca con su idioma cuando la página está en otro.

    El contenido del sitio está en español y se queda en español: es la lengua
    en la que el hospital publica. Lo que se traduce es la interfaz. De ahí que
    una página pueda ser <html lang="en"> y llevar dentro párrafos en español, y
    que haya que decirlo:

    · Un lector de pantalla en inglés leería el español con fonética inglesa y
      saldría ininteligible. Es lo que exige el criterio 3.1.2 de WCAG, «idioma
      de las partes».
    · El traductor del navegador daría por inglés ese texto y no lo traduciría,
      que es justo lo que el visitante le está pidiendo.

    El escapado lo decide quien llama, como en cualquier plantilla: {{ }} para
    títulos y resúmenes, {!! !!} para los cuerpos, que ya vienen saneados. La
    ranura llega aquí ya compuesta, así que no se vuelve a escapar.

    Para el texto que va dentro de un atributo —`alt`, `title`— no sirve
    envolver: el idioma se declara en el elemento que lo lleva. Para eso está
    PortalLang::attribute().
--}}
<{{ $tag }} {{ $attributes->merge(PortalLang::marks()) }}>{{ $slot }}</{{ $tag }}>
