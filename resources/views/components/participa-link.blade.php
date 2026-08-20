@props(['item'])

@if ($item->invitesParticipation())
    {{-- En el portal institucional es un botón que lleva al propio contenido;
         aquí es un enlace con aspecto de botón, porque navegar es lo que hace y
         así funciona con teclado y con lector de pantalla.
         Es público, no @auth: no es un distintivo de moderación. --}}
    <a href="{{ $item->url() }}"
       {{ $attributes->class([
           'w-fit rounded-[2px] bg-azure px-3 py-[3px] font-display text-10-5 font-bold',
           'tracking-[0.06em] text-on-accent uppercase no-underline',
           'hover:bg-azure-dark hover:no-underline',
       ]) }}>
        {{ __('componentes.participa.rotulo') }}<span class="sr-only"> {!! __('componentes.participa.en', ['titulo' => App\Support\PortalLang::wrap(Str::limit($item->title, 60))]) !!}</span>
    </a>
@endif
