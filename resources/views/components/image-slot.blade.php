@props([
    /** Ruta dentro de public/ (p. ej. 'img/noticias/cartago.jpg'), o URL absoluta. */
    'src' => null,
    'alt' => '',
    /** Texto de referencia mostrado mientras no se ha cargado la imagen real. */
    'hint' => null,
    /** true para la imagen «above the fold»: se carga con prioridad, sin lazy. */
    'priority' => false,
    /** Radio de borde en px. */
    'radius' => null,
    /** Muestra el contorno punteado del marcador de posición. */
    'bordered' => true,
])

@php
    $style = $radius ? "border-radius: {$radius}px" : null;
@endphp

@if ($src)
    <img
        src="{{ Str::startsWith($src, ['http://', 'https://', '/']) ? $src : asset($src) }}"
        alt="{{ $alt }}"
        loading="{{ $priority ? 'eager' : 'lazy' }}"
        fetchpriority="{{ $priority ? 'high' : 'low' }}"
        decoding="async"
        @if ($style) style="{{ $style }}" @endif
        {{ $attributes->class('block h-full w-full object-cover') }}
    />
@else
    {{-- Marcador de posición: decorativo, no se anuncia a lectores de pantalla. --}}
    <div
        aria-hidden="true"
        @if ($style) style="{{ $style }}" @endif
        {{ $attributes->class([
            'flex h-full w-full items-center justify-center bg-tint p-4 text-center',
            'border border-dashed border-stroke-strong' => $bordered,
        ]) }}
    >
        <span class="text-12 leading-relaxed font-medium text-faint">
            {{ $hint ?? 'Imagen pendiente' }}
        </span>
    </div>
@endif
