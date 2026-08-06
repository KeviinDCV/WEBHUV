{{--
    Encuesta de utilidad del contenido.

    Es puramente de cliente: guarda la respuesta en el navegador y agradece.
    No se envía nada al servidor todavía —haría falta decidir dónde se
    consultan esos datos— pero el bloque ya ocupa su sitio en la página.

    `$key` identifica la página dentro del navegador; ha de ser único entre
    tipos de contenido para que responder en una noticia no dé por respondida
    la encuesta de un documento con el mismo número.
--}}
<div x-data="{
        answered: false,
        init() {
            try {
                this.answered = localStorage.getItem('huv:util:{{ $key }}') !== null;
            } catch {}
        },
        answer(useful) {
            this.answered = true;
            try {
                localStorage.setItem('huv:util:{{ $key }}', useful ? '1' : '0');
            } catch {}
        },
     }"
     class="rounded-[4px] bg-navy px-6 py-5 text-on-brand">

    <div x-show="! answered" class="flex flex-wrap items-center justify-between gap-4">
        <p class="m-0 text-14-5 leading-[1.5]">
            <span class="block font-semibold">¿Encontraste lo que buscabas?</span>
            <span class="block">¿Te pareció útil este contenido?</span>
        </p>

        <div class="flex gap-2">
            <button type="button" @click="answer(true)"
                    class="rounded-[3px] border border-on-brand/60 bg-transparent px-5 py-2 text-14
                           font-semibold text-on-brand transition-colors hover:bg-white/15">
                Sí
            </button>
            <button type="button" @click="answer(false)"
                    class="rounded-[3px] border border-on-brand/60 bg-transparent px-5 py-2 text-14
                           font-semibold text-on-brand transition-colors hover:bg-white/15">
                No
            </button>
        </div>
    </div>

    <p x-show="answered" x-cloak class="m-0 text-14-5" role="status">
        Gracias por su respuesta.
    </p>
</div>
