@php $institution = config('huv.institution'); @endphp
<!DOCTYPE html>
<html lang="es-CO" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#2b3b80">

    <title>@yield('title', 'Administración') — WEB Huv</title>

    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">

    <script>
        (function () {
            try {
                var saved = JSON.parse(localStorage.getItem('huv:a11y') || '{}');
                document.documentElement.dataset.huvContrast = saved.contrast ? 'on' : 'off';
                if (saved.fontStep) {
                    document.documentElement.style.fontSize =
                        (16 + Math.min(3, Math.max(-1, saved.fontStep)) * 2) + 'px';
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
{{-- Ver la nota sobre `x-data` en layouts/app.blade.php. --}}
<body x-data class="bg-surface font-sans text-ink">

    <a href="#contenido" class="huv-skip">Saltar al contenido principal</a>

    <header class="bg-navy text-on-brand">
        <x-container class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 py-3">
            <p class="m-0 font-display text-15 font-bold tracking-[0.04em]">
                WEB Huv <span class="font-normal opacity-80">· Administración</span>
            </p>

            <div class="flex items-center gap-5 text-13">
                <span class="opacity-90">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="border-0 bg-transparent p-0 text-13 font-semibold text-on-brand underline underline-offset-4">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </x-container>
    </header>

    <div class="border-b border-line bg-card">
        <x-container class="flex flex-wrap items-center gap-x-8 gap-y-2 py-5">
            <a href="{{ $backUrl ?? route('home') }}"
               class="inline-flex items-center gap-2 font-display text-16 font-bold text-heading no-underline hover:text-heading-hover">
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 5-7 7 7 7" />
                </svg>
                Atrás
            </a>
            <p class="m-0 font-display text-19 text-heading lg:text-22">{{ $institution['name_plain'] }}</p>
        </x-container>
    </div>

    <main id="contenido" tabindex="-1">
        <x-container class="py-9">
            <h1 class="m-0 font-display text-19 font-bold text-heading">@yield('heading')</h1>
            @hasSection('subheading')
                <p class="m-0 mt-1 text-14 text-muted">@yield('subheading')</p>
            @endif

            @if (session('status'))
                <p role="status"
                   class="m-0 mt-5 rounded-[3px] border border-line border-l-4 border-l-rule-accent bg-card
                          px-4 py-3 text-13-5 text-heading">
                    {{ session('status') }}
                </p>
            @endif

            @if ($errors->any())
                <div role="alert"
                     class="mt-5 rounded-[3px] border border-line border-l-4 border-l-[#b3261e] bg-[#fdf3f2] px-4 py-3">
                    <p class="m-0 text-13-5 font-semibold text-[#8c1d18]">Revise los siguientes puntos</p>
                    <ul class="m-0 mt-1 flex list-disc flex-col gap-1 pl-5 text-13-5 text-[#8c1d18]">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-7">
                @yield('content')
            </div>
        </x-container>
    </main>

    @include('partials.accessibility-rail')
</body>
</html>
