@props(['title', 'url' => null])

@php
    $shareUrl = $url ?? url()->current();

    /*
     | Enlaces de compartir, no widgets de las redes.
     |
     | Los botones oficiales de Facebook o X cargan sus scripts y rastrean a
     | quien entra a leer una noticia, aunque no pulse nada. Un enlace normal
     | hace lo mismo de cara al usuario sin ceder datos a nadie.
    */
    $networks = [
        [
            'name' => 'Facebook',
            'url' => 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($shareUrl),
            'path' => 'M14 8.5h2.5V5.5H14c-2 0-3.5 1.5-3.5 3.5v1.5H8.5v3h2v6h3v-6h2.3l.4-3h-2.7V9c0-.3.2-.5.5-.5Z',
        ],
        [
            'name' => 'X',
            'url' => 'https://x.com/intent/post?text='.urlencode($title).'&url='.urlencode($shareUrl),
            'path' => 'M17.2 4h2.6l-5.7 6.5L20.8 20h-5.2l-4.1-5.4L6.7 20H4.1l6.1-7L3.6 4h5.4l3.7 4.9zm-.9 14.4h1.4L8.2 5.5H6.7z',
        ],
        [
            'name' => 'WhatsApp',
            'url' => 'https://api.whatsapp.com/send?text='.urlencode($title.' '.$shareUrl),
            'path' => 'M12 3.5a8.4 8.4 0 0 0-7.2 12.8L4 20.5l4.4-.8A8.4 8.4 0 1 0 12 3.5Zm4.5 11.7c-.2.5-1.1 1-1.5 1-.4.1-.9.1-1.5-.1a12 12 0 0 1-4.5-3.6c-.6-.9-1-1.9-1-2.7 0-.8.4-1.2.6-1.4.2-.2.4-.3.6-.3h.4c.2 0 .3 0 .5.4l.6 1.5c.1.2 0 .3 0 .4l-.3.4-.2.3c-.1.1-.2.2 0 .5.2.3.7 1.1 1.4 1.7.9.8 1.6 1 1.9 1.2.2.1.4 0 .5-.1l.6-.7c.1-.2.3-.1.5 0l1.4.7c.2.1.3.2.4.3v.5Z',
        ],
        [
            'name' => 'Correo',
            'url' => 'mailto:?subject='.rawurlencode($title).'&body='.rawurlencode($shareUrl),
            'path' => 'M3.5 6.5h17v11h-17zM3.5 7l8.5 6 8.5-6',
            'stroke' => true,
        ],
    ];
@endphp

<div {{ $attributes->class('flex flex-wrap items-center gap-3') }}>
    <span class="text-13-5 font-semibold text-heading">Compartir</span>

    <ul class="flex items-center gap-2">
        @foreach ($networks as $network)
            <li>
                <a href="{{ $network['url'] }}" target="_blank" rel="noopener noreferrer"
                   class="flex size-8 items-center justify-center rounded-full bg-azure text-on-accent
                          no-underline transition-colors hover:bg-azure-dark hover:text-on-accent hover:no-underline">
                    <svg class="size-4" viewBox="0 0 24 24"
                         @if ($network['stroke'] ?? false)
                             fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round"
                         @else
                             fill="currentColor"
                         @endif
                         aria-hidden="true">
                        <path d="{{ $network['path'] }}" />
                    </svg>
                    <span class="sr-only">Compartir en {{ $network['name'] }} (se abre en una pestaña nueva)</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
