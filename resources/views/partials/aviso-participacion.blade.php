@if ($item->invitesParticipation())
    {{--
        En el portal institucional, «Participa» abre un muro de comentarios que
        este aplicativo todavía no tiene. Se dice por dónde se participa en
        lugar de prometer algo que no está.
    --}}
    <aside class="mt-8 rounded-[4px] border border-line border-l-4 border-l-rule-accent bg-tint px-5 py-4">
        <p class="m-0 text-13-5 text-body">
            {{ __('estructura.participacion.aviso') }}
        </p>
    </aside>
@endif
