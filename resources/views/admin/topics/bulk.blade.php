@extends('layouts.admin')

@section('title', 'Carga masiva — '.$topic->name)

@section('content')
    <x-container class="py-8 lg:py-10">
        <div class="mx-auto max-w-[720px]">

            <nav aria-label="Ruta de navegación" class="mb-4">
                <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                    <li><a href="{{ route('home') }}" class="text-link">Inicio</a></li>
                    <li aria-hidden="true">›</li>
                    <li><a href="{{ route('topics.show', $topic) }}" class="text-link">{{ $topic->name }}</a></li>
                    <li aria-hidden="true">›</li>
                    <li aria-current="page" class="font-semibold text-heading">Carga masiva</li>
                </ol>
            </nav>

            <h1 class="m-0 font-display text-25 font-bold text-heading">
                Carga masiva de {{ Str::lower($topic->name) }}
            </h1>

            @if ($errors->any())
                <div role="alert"
                     class="mt-6 rounded-[3px] border border-line border-l-4 border-l-danger bg-danger-surface px-4 py-3">
                    <p class="m-0 text-13-5 font-semibold text-danger">No se pudo cargar el archivo</p>
                    <ul class="m-0 mt-1 flex list-disc flex-col gap-1 pl-5 text-13-5 text-danger">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" enctype="multipart/form-data" class="mt-6"
                  action="{{ route('admin.topics.bulk.store', $topic) }}"
                  x-data="{ archivo: '' }">
                @csrf

                <h2 class="m-0 font-display text-17 font-bold text-heading">
                    Tenga en cuenta las siguientes recomendaciones
                </h2>

                <p class="m-0 mt-4 font-display text-15 font-semibold text-heading">
                    Suba el archivo con los datos a cargar
                </p>

                <ol class="mt-3 flex list-decimal flex-col gap-2 pl-5 text-14 text-body">
                    <li>El archivo debe ser en formato Excel (xlsx).</li>
                    <li>
                        Debe tener 3 columnas.
                        <ul class="mt-1 flex list-none flex-col gap-1 text-13-5 text-muted">
                            <li>— La primera el nombre (requerida, máximo 200 caracteres)</li>
                            <li>— La segunda la descripción (requerida)</li>
                            <li>— La tercera la dirección (requerida)</li>
                        </ul>
                    </li>
                    <li>Debe ir sin encabezado.</li>
                </ol>

                <label for="archivo"
                       class="mt-6 flex w-full cursor-pointer items-center gap-3 rounded-[3px] border
                              border-dashed border-stroke-strong bg-card px-5 py-4 hover:bg-tint">
                    <svg class="size-5 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 7V5a1 1 0 0 1 1-1h4l2 2h8a1 1 0 0 1 1 1v2" />
                        <path d="M3 8h18l-1.5 10a1 1 0 0 1-1 1H5.5a1 1 0 0 1-1-1Z" />
                    </svg>
                    <span class="text-14 text-link underline underline-offset-4">Agregar archivo</span>
                    <span class="text-13 text-muted" x-text="archivo"></span>
                </label>
                <input id="archivo" name="archivo" type="file" accept=".xlsx" required class="sr-only"
                       @change="archivo = $event.target.files[0]?.name ?? ''">

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <button type="submit"
                            class="rounded-full border-0 bg-azure px-7 py-[10px] font-display text-12-5 font-bold
                                   tracking-[0.06em] text-on-accent uppercase transition-colors hover:bg-azure-dark">
                        Cargar
                    </button>

                    <a href="{{ route('topics.show', $topic) }}"
                       class="text-13-5 font-semibold text-link underline underline-offset-4">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </x-container>
@endsection
