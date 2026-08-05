# WEB Huv

Renovación del portal institucional del **Hospital Universitario del Valle «Evaristo García» E.S.E.**

Reemplazo del sitio actual en micolombiadigital.gov.co, construido sobre el mismo stack que el resto
de aplicativos del hospital.

## Stack

| Capa      | Tecnología                                        |
|-----------|---------------------------------------------------|
| Backend   | Laravel 12 · PHP 8.2                              |
| Front     | Vite 6 · Tailwind CSS 4 · Alpine.js 3             |
| Tipografía| Montserrat + Work Sans (self-hosted, `@fontsource`)|

## Puesta en marcha

```bash
composer install && npm install && npm run build
```

Desarrollo (servidor, colas, logs y Vite en paralelo):

```bash
composer dev
```

O por separado:

```bash
php artisan serve --port=8001
```

```bash
npm run dev
```

## Estructura

```
app/Http/Controllers/HomeController.php   Página de inicio
config/huv.php                            Todo el contenido institucional
resources/views/
  layouts/app.blade.php                   Layout: SEO, OG, JSON-LD, a11y
  partials/                               header, nav, footer, rail a11y, JSON-LD
  sections/                               hero, accesos rápidos, entidad, noticias…
  components/                             container, image-slot
resources/js/components/                  Alpine: carousel, nav, a11y
tests/Feature/HomePageTest.php            Test de humo de la home
```

### Contenido

Mientras no exista administración de contenidos, **todo el texto vive en `config/huv.php`**:
menús, accesos rápidos, noticias, especialidades, ítems de transparencia y datos de contacto.
Las vistas no llevan texto quemado, de modo que cada bloque puede migrarse a base de datos sin
tocar el Blade.

Los enlaces de páginas aún no construidas apuntan a `'#'`. Al crear cada página basta con
reemplazar ese valor por `route('...')`.

### Imágenes

Las fotografías (banners, fachada, noticias) están como **marcadores de posición**: el componente
`<x-image-slot>` muestra un recuadro con la descripción y las medidas sugeridas mientras el campo
`image` del config sea `null`. Al asignarle una ruta dentro de `public/` renderiza un `<img>` real
con `loading`, `decoding` y `fetchpriority` ya resueltos.

```php
// config/huv.php
'image' => 'img/banners/70-anios.jpg',
```

## Mejoras respecto al sitio original

**Responsive** — el sitio actual es de ancho fijo. Aquí las rejillas se adaptan (4 → 2 → 1 columna),
el menú se convierte en un cajón lateral con acordeones por debajo de `lg`, y el banner ajusta su
alto al contenido.

**Alto contraste** — es un tema de color real, no un `filter: invert()`. La paleta vive en tokens
semánticos en `resources/css/app.css` y el bloque `html[data-huv-contrast='on']` redefine sus valores;
el JS solo conmuta el atributo. Negro / blanco / amarillo, todos los pares por encima de 7:1 (AAA).
Incluye soporte de `forced-colors` para el modo de contraste del sistema operativo.

> **Nota de color.** El acento del diseño original (`#2b7fe0`) da 4.03:1 con texto blanco, por debajo
> del 4.5:1 del nivel AA que exige la Resolución 1519 de 2020 vía NTC 5854. Se oscureció al mínimo
> imprescindible (`#2676d2`, 4.56:1) en `--color-azure` y `--color-link`. El filete decorativo
> conserva el tono original en `--color-rule-accent`. Para volver al color exacto del diseño basta con
> revertir esas dos variables.

**Accesibilidad**
- Enlace «Saltar al contenido principal» y landmarks correctos.
- Carrusel conforme a WCAG 2.2.2: se detiene al pasar el ratón o al enfocar, respeta
  `prefers-reduced-motion`, admite flechas del teclado y gesto táctil, y anuncia su posición.
- Menús con `aria-expanded`/`aria-controls`, cierre con `Escape` devolviendo el foco al disparador.
- El control «A+ / A−» escala de verdad todo el texto (tipografía en `rem` sobre `<html>`); en el
  diseño original no tenía efecto sobre los textos en `px`.
- El alto contraste re-invierte imágenes y vídeo para que las fotografías conserven sus colores.
- Ambas preferencias persisten en `localStorage` y se aplican antes del primer pintado.

**SEO** — título, descripción, canonical, Open Graph, Twitter Card y datos estructurados JSON-LD
(`Hospital` + `GovernmentOrganization`) con dirección, NIT, teléfonos y especialidades.

**Rendimiento** — tipografías self-hosted (sin peticiones a Google Fonts, funciona en intranet),
CSS y JS empaquetados por Vite, `lazy loading` en todas las imágenes salvo el primer banner,
iconografía en SVG en lugar de glifos tipográficos. El logotipo se sirve a 620×175 px (30 KB en vez
de 66 KB) y declara su proporción real en `width`/`height`, lo que evita el salto de maquetación.

## Hora legal

El pie muestra la hora legal de la República de Colombia. El servidor inyecta su marca de tiempo y el
navegador solo la hace avanzar, de modo que un reloj mal ajustado en el equipo del visitante no
altera lo que se ve; sin JavaScript la hora se sigue mostrando, solo deja de correr.

> Para que la cifra sea **legalmente exacta**, el servidor debe sincronizarse por NTP contra el
> Instituto Nacional de Metrología (`hora.inm.gov.co`). Es configuración del sistema operativo, no
> del frontend. Alternativa: incrustar el widget oficial de <https://horalegal.inm.gov.co/>, a costa
> de una dependencia externa que no funciona en intranet.

## Tests

```bash
php artisan test
```
