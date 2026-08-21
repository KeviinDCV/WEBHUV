/**
 * Mampostería: cada tarjeta arranca donde acaba la anterior de SU columna.
 *
 * Es lo que hace el portal actual. Con una rejilla normal, la fila entera mide
 * lo que la tarjeta más alta y debajo de las cortas queda un blanco hasta que
 * empieza la fila siguiente; aquí la tarjeta de abajo sube a ocupar ese hueco.
 *
 * El navegador todavía no lo hace solo —`grid-template-rows: masonry` no está
 * en Chrome—, así que se consigue con el truco de las filas de un píxel: la
 * rejilla se declara con `grid-auto-rows: 1px` y a cada tarjeta se le dice
 * cuántas de esas filas ocupa. Al ser tantas y tan finas, cada columna avanza
 * como si fuera independiente.
 *
 * Se hace desde JavaScript y no desde la hoja de estilos a propósito: sin
 * JavaScript, `grid-auto-rows: 1px` dejaría las tarjetas amontonadas en un
 * píxel. Sin él, la rejilla se queda como está —alineada arriba, con el hueco
 * fuera— que es un listado perfectamente legible.
 *
 * El orden no se toca: las tarjetas siguen colocándose por `order`, así que
 * ordenar y filtrar desde Alpine sigue funcionando igual.
 */

/** Alto de la separación entre tarjetas, leído de la propia rejilla. */
function separacion(rejilla, estilo) {
    // `column-gap` sobrevive a que pongamos `row-gap` a cero, así que es de
    // donde se lee el valor que la plantilla declaró con `gap-*`.
    const valor = parseFloat(estilo.columnGap);

    return Number.isFinite(valor) ? valor : 0;
}

/** ¿Cuántas columnas tiene ahora mismo? En una sola no hay nada que cuadrar. */
function columnas(estilo) {
    const plantilla = estilo.gridTemplateColumns;

    return plantilla && plantilla !== 'none' ? plantilla.split(/\s+/).filter(Boolean).length : 1;
}

/** Deshace la mampostería y devuelve la rejilla a su comportamiento normal. */
function limpiar(rejilla) {
    rejilla.style.gridAutoRows = '';
    rejilla.style.rowGap = '';

    for (const celda of rejilla.children) {
        celda.style.gridRowEnd = '';
        celda.style.marginBottom = '';
    }
}

/**
 * Recoloca las tarjetas de una rejilla.
 *
 * @param {HTMLElement|null} rejilla
 */
export function acomodar(rejilla) {
    if (!rejilla) {
        return;
    }

    const estilo = window.getComputedStyle(rejilla);

    if (estilo.display !== 'grid' || columnas(estilo) < 2) {
        limpiar(rejilla);

        return;
    }

    const hueco = separacion(rejilla, estilo);

    rejilla.style.gridAutoRows = '1px';
    rejilla.style.rowGap = '0px';

    for (const celda of rejilla.children) {
        // Lo oculto no ocupa: `x-show` le pone `display: none` y medirlo daría
        // cero, que como tramo de filas dejaría un hueco de un píxel por cada
        // tarjeta filtrada.
        if (celda.offsetParent === null && celda.style.display === 'none') {
            celda.style.gridRowEnd = '';
            celda.style.marginBottom = '';

            continue;
        }

        // Se mide la tarjeta y no la celda: la celda ya lleva el tramo de la
        // vuelta anterior, y medirla sería medir el resultado de la medición.
        const tarjeta = celda.firstElementChild ?? celda;
        const alto = tarjeta.getBoundingClientRect().height;

        if (alto <= 0) {
            continue;
        }

        celda.style.marginBottom = hueco + 'px';
        celda.style.gridRowEnd = 'span ' + Math.ceil(alto + hueco);
    }
}

/**
 * Acomoda y se queda vigilando lo que puede cambiar las alturas.
 *
 * Una foto que termina de cargar, una fuente que llega tarde o la ventana que
 * se estrecha cambian el alto de una tarjeta, y con el tramo de filas viejo la
 * columna se descuadra. `ResizeObserver` lo cubre todo de una vez: se dispara
 * cuando cambia el tamaño de cualquiera de las tarjetas o de la propia rejilla.
 *
 * @param {HTMLElement|null} rejilla
 * @returns {() => void} para dejar de vigilar
 */
export function vigilar(rejilla) {
    if (!rejilla) {
        return () => {};
    }

    let pedida = false;

    // Se agrupa en un solo fotograma: al cargar, veinte fotos terminan casi a
    // la vez y recalcular con cada una sería veinte recorridos seguidos.
    //
    // Con la pestaña en segundo plano el navegador no dibuja fotogramas y
    // `requestAnimationFrame` no llega a ejecutarse: quien abre la portada en
    // una pestaña nueva sin mirarla se encontraba el listado sin acomodar. Ahí
    // se recurre al temporizador, que sí corre.
    const acomodarPronto = () => {
        if (pedida) {
            return;
        }

        pedida = true;

        const hacerlo = () => {
            pedida = false;
            acomodar(rejilla);
        };

        if (document.hidden) {
            window.setTimeout(hacerlo, 0);
        } else {
            window.requestAnimationFrame(hacerlo);
        }
    };

    // La primera pasada, sin esperar a nada: es la que evita que el listado se
    // vea un instante con los huecos antes de cuadrarse.
    acomodar(rejilla);

    if (typeof ResizeObserver === 'undefined') {
        window.addEventListener('resize', acomodarPronto);

        return () => window.removeEventListener('resize', acomodarPronto);
    }

    const observador = new ResizeObserver(acomodarPronto);

    observador.observe(rejilla);

    for (const celda of rejilla.children) {
        const tarjeta = celda.firstElementChild ?? celda;
        observador.observe(tarjeta);
    }

    return () => observador.disconnect();
}
