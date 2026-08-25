@auth
    {{-- Barra de administración: solo existe en el HTML si hay sesión iniciada.
         El interruptor de abajo únicamente decide si se ven los controles de
         edición; quien decide si existen es el servidor. --}}
    <div class="border-b border-navy-dark bg-navy-deep text-on-brand print:hidden">
        <x-container class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 py-2 text-13">

            <p class="m-0 flex items-center gap-2">
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="3.6" />
                    <path d="M4.5 20a7.5 7.5 0 0 1 15 0" />
                </svg>
                <span>
                    {{ __('estructura.barra_admin.sesion_iniciada') }}
                    <strong class="font-semibold">{{ auth()->user()->name }}</strong>
                </span>
            </p>

            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <button type="button"
                        @click="$store.huvUi.toggleEdit()"
                        :aria-pressed="$store.huvUi.editMode ? 'true' : 'false'"
                        class="flex items-center gap-2 border-0 bg-transparent p-0 text-13 font-semibold text-on-brand">
                    <span class="relative inline-flex h-[18px] w-8 shrink-0 rounded-full transition-colors"
                          :class="$store.huvUi.editMode ? 'bg-azure' : 'bg-white/30'"
                          aria-hidden="true">
                        <span class="absolute top-[2px] left-[2px] size-[14px] rounded-full bg-white transition-transform"
                              :class="$store.huvUi.editMode && 'translate-x-[14px]'"></span>
                    </span>
                    {{ __('estructura.barra_admin.controles_edicion') }}
                </button>

                {{-- El menú es lo único de la administración que no cuelga de
                     una página: no es de ninguna, es de todas. Por eso se llega
                     desde aquí y no con un lápiz sobre una sección. --}}
                <a href="{{ route('admin.menu.index') }}"
                   class="text-13 font-semibold text-on-brand underline underline-offset-4">
                    {{ __('estructura.barra_admin.menu') }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="border-0 bg-transparent p-0 text-13 font-semibold text-on-brand underline underline-offset-4">
                        {{ __('estructura.barra_admin.cerrar_sesion') }}
                    </button>
                </form>
            </div>
        </x-container>
    </div>
@endauth

@if (session('status'))
    <div role="status"
         class="border-b border-line bg-tint print:hidden">
        <x-container class="py-3 text-13-5 text-heading">{{ session('status') }}</x-container>
    </div>
@endif
