@php
    $institution = config('huv.institution');
    $seo = config('huv.seo');
    $pageTitle = trim($__env->yieldContent('title')) ?: $seo['title'];
    $pageDescription = trim($__env->yieldContent('description')) ?: $seo['description'];

    // Una ficha con imagen propia la declara aquí, y no apilando un segundo
    // juego de etiquetas: al compartir el enlace gana la primera que aparece, y
    // sería siempre la genérica.
    $pageType = trim($__env->yieldContent('og_type')) ?: 'website';
    $pageImage = trim($__env->yieldContent('og_image')) ?: asset('img/og-huv.png');
@endphp
<!DOCTYPE html>
<html lang="es-CO" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="keywords" content="{{ $seo['keywords'] }}">
    <meta name="author" content="{{ $institution['name'] }}">
    <meta name="theme-color" content="#2b3b80">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="{{ $pageType }}">
    <meta property="og:locale" content="es_CO">
    <meta property="og:site_name" content="{{ $institution['name'] }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">

    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">

    {{-- Preferencias de accesibilidad antes del primer pintado: evita el
         parpadeo al recargar con alto contraste o texto ampliado activos. --}}
    <script>
        (function () {
            try {
                var saved = JSON.parse(localStorage.getItem('huv:a11y') || '{}');
                var root = document.documentElement;
                root.dataset.huvContrast = saved.contrast ? 'on' : 'off';
                if (saved.fontStep) {
                    root.style.fontSize = (16 + Math.min(3, Math.max(-1, saved.fontStep)) * 2) + 'px';
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')

    @include('partials.structured-data')
</head>
{{--
    `x-data` en el <body>: Alpine solo inicializa los árboles que cuelgan de un
    elemento con x-data. Sin esto, cualquier `x-show="$store…"` que no esté
    dentro de un componente —los botones de edición de las secciones, el
    interruptor de la barra de administración— nunca se procesa y el `x-cloak`
    lo deja oculto de forma permanente.
--}}
<body x-data class="bg-page font-sans text-ink">

    <a href="#contenido" class="huv-skip">Saltar al contenido principal</a>

    @include('partials.admin-bar')
    @include('partials.header')
    @include('partials.nav')

    <main id="contenido" tabindex="-1">
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.accessibility-rail')
    @include('partials.back-to-top')
    @include('partials.leave-site-modal')

    @stack('scripts')
</body>
</html>
