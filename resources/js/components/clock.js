/**
 * Reloj de la hora legal de la República de Colombia.
 *
 * Se ancla a la marca de tiempo del servidor en lugar de al reloj del equipo
 * del visitante: al iniciar se calcula el desfase entre ambos y a partir de ahí
 * solo se avanza en local. Un portátil con la hora mal puesta ya no altera lo
 * que se muestra.
 *
 * La hora legal la determina el Instituto Nacional de Metrología; para que la
 * cifra sea exacta el servidor debe estar sincronizado por NTP contra el INM.
 */
export default function huvClock(serverEpochMs, timeZone = 'America/Bogota') {
    return {
        display: '--:--:--',
        isoValue: '',
        offset: 0,
        timer: null,
        formatter: null,

        init() {
            this.offset = serverEpochMs - Date.now();

            this.formatter = new Intl.DateTimeFormat('es-CO', {
                timeZone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });

            this.tick();

            // Alinea el primer salto con el segundo en curso para que la cifra
            // no cambie a destiempo, y a partir de ahí marca cada segundo.
            const untilNextSecond = 1000 - ((Date.now() + this.offset) % 1000);

            window.setTimeout(() => {
                this.tick();
                this.timer = window.setInterval(() => this.tick(), 1000);
            }, untilNextSecond);

            // Al volver de una pestaña en segundo plano el intervalo puede
            // haberse ralentizado: se repinta de inmediato.
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.tick();
            });
        },

        destroy() {
            if (this.timer) window.clearInterval(this.timer);
        },

        tick() {
            const now = new Date(Date.now() + this.offset);

            this.display = this.formatter.format(now);
            this.isoValue = now.toISOString();
        },
    };
}
