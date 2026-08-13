/**
 * Mapa de ubicación.
 *
 * Leaflet sobre teselas de OpenStreetMap, que es lo mismo que usa el portal
 * actual. Aquí cambian tres cosas, todas por lo mismo —que el mapa no estorbe
 * a quien solo pasaba por encima—:
 *
 * 1. La biblioteca se carga bajo demanda. Leaflet pesa unos 150 kB y solo hay
 *    un mapa en todo el sitio: cargarlo desde `app.js` se lo comería cada
 *    visitante de cada página. Con `import()` dinámico, Vite lo deja en un
 *    fragmento aparte que solo se pide donde hay mapa.
 *
 * 2. La rueda del ratón no acerca el mapa hasta que se pulsa dentro. Un mapa
 *    de 500 px de alto y todo el ancho es imposible de sobrepasar con la
 *    rueda si además hace zoom; en el portal actual, si el mapa te pilla de
 *    paso, te quedas atrapado en él.
 *
 * 3. Si la carga falla —red caída, bloqueador de terceros— se queda el aviso
 *    que ya venía en el HTML, con la dirección y el enlace a OpenStreetMap.
 *    Por eso el contenedor no se vacía hasta tener Leaflet en la mano.
 */
export default function huvMap({ latitude, longitude, zoom = 16, label = '', address = '' }) {
    return {
        map: null,
        hint: false,
        timer: null,

        async init() {
            let L;

            try {
                const [leaflet] = await Promise.all([
                    import('leaflet'),
                    import('leaflet/dist/leaflet.css'),
                ]);

                // Leaflet 1.9 se publica en CommonJS: según cómo lo empaquete
                // Vite, los símbolos llegan en `default` o sueltos.
                L = leaflet.default ?? leaflet;
            } catch {
                return; /* Se queda la alternativa en texto. */
            }

            const canvas = this.$refs.canvas;

            // El aviso alternativo desaparece justo ahora, no antes: si algo
            // fallara a mitad, quedaría un hueco gris sin explicación.
            canvas.replaceChildren();
            canvas.removeAttribute('hidden');

            // Quien pide menos movimiento no quiere ver el mapa deslizándose
            // ni las teselas apareciendo con transición.
            const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            this.map = L.map(canvas, {
                center: [latitude, longitude],
                zoom,
                scrollWheelZoom: false,
                zoomAnimation: !still,
                fadeAnimation: !still,
                markerZoomAnimation: !still,
            });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                // La licencia de OpenStreetMap obliga a atribuir, y Leaflet no
                // lo pone solo si se le pasa un `attribution` vacío.
                attribution: '© <a href="https://www.openstreetmap.org/copyright" '
                    + 'target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
            }).addTo(this.map);

            // Chincheta propia en vez de la imagen que trae Leaflet: la suya se
            // sirve como PNG desde la carpeta de la biblioteca —el portal
            // actual la pide a unpkg.com— y usa un azul que no es el del sitio.
            // Dibujada en SVG hereda el color del tema, así que en alto
            // contraste se vuelve amarilla con todo lo demás.
            const marker = L.marker([latitude, longitude], {
                keyboard: true,
                title: label,
                alt: label,
                icon: L.divIcon({
                    className: 'huv-map-pin',
                    // El lienzo es de 24 × 32 y la chincheta lo llena entera:
                    // así la punta cae justo en el borde inferior y coincide
                    // con `iconAnchor`, que es el punto que se clava en las
                    // coordenadas.
                    html: '<svg viewBox="0 0 24 32" aria-hidden="true">'
                        + '<path d="M12 0a12 12 0 0 0-12 12c0 9 12 20 12 20s12-11 12-20A12 12 0 0 0 12 0Z" />'
                        + '<circle cx="12" cy="12" r="4.5" />'
                        + '</svg>',
                    iconSize: [30, 40],
                    iconAnchor: [15, 40],
                    popupAnchor: [0, -38],
                }),
            }).addTo(this.map);

            const directions = 'https://www.openstreetmap.org/directions?to='
                + encodeURIComponent(`${latitude},${longitude}`);

            marker.bindPopup(
                `<strong class="huv-map-name">${escape(label)}</strong>`
                + (address ? `<span class="huv-map-address">${escape(address)}</span>` : '')
                + `<a class="huv-map-route" href="${directions}" target="_blank" rel="noopener noreferrer">`
                + 'Cómo llegar<span class="sr-only"> (se abre en una pestaña nueva)</span></a>',
                { closeButton: true, maxWidth: 260 }
            ).openPopup();

            // Arrastrar, pellizcar, los botones de zoom y el teclado funcionan
            // desde el primer momento. La rueda es la única que espera a que se
            // pulse dentro —o a que se llegue con el tabulador—, y al salir
            // vuelve a dejar pasar el desplazamiento de la página.
            const wheel = (on) => () => {
                this.map.scrollWheelZoom[on ? 'enable' : 'disable']();

                if (on) {
                    this.hint = false;
                }
            };

            this.map.on('click focus', wheel(true));
            this.map.on('mouseout blur', wheel(false));

            // Y se avisa de ello: sin el aviso, quien intente acercar con la
            // rueda y vea la página seguir de largo pensará que el mapa está
            // roto, no que le falta un clic.
            canvas.addEventListener('wheel', () => {
                if (this.map.scrollWheelZoom.enabled()) {
                    return;
                }

                this.hint = true;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => (this.hint = false), 1600);
            }, { passive: true });

            // Leaflet le pone `tabindex` al contenedor, así que se tabula hasta
            // él y hay que decir qué es.
            canvas.setAttribute('aria-label', address ? `Mapa: ${label}, ${address}` : `Mapa: ${label}`);
        },

        destroy() {
            clearTimeout(this.timer);
            this.map?.remove();
        },
    };
}

/** El rótulo sale de la configuración, pero acaba dentro de HTML. */
function escape(value) {
    const box = document.createElement('span');
    box.textContent = value;

    return box.innerHTML;
}
