<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Database\Seeders\MenuSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * El menú del portal: la barra de la cabecera y el menú del botón ☰.
 *
 * Hasta ahora la navegación vivía en config/huv.php y cambiarla era un cambio
 * de código: en la práctica, el hospital no podía. Aquí se edita.
 *
 * Mientras la tabla está vacía, el portal sirve el menú de la configuración y
 * esta pantalla enseña ese mismo menú en solo lectura, con un botón para
 * copiarlo a la base. Se hace así y no volcándolo al entrar porque entrar a
 * mirar no debe cambiar nada.
 */
class MenuController extends Controller
{
    /** El menú entero, las dos áreas, para revisarlo de un vistazo. */
    public function index(): View
    {
        return view('admin.menu.index', [
            'areas' => collect(MenuItem::AREAS)->mapWithKeys(fn (string $area): array => [
                $area => MenuItem::query()
                    ->where('area', $area)
                    ->whereNull('parent_id')
                    ->with(['children' => fn ($q) => $q->orderBy('position')])
                    ->orderBy('position')
                    ->get(),
            ]),
            'sinEditar' => collect(MenuItem::AREAS)
                ->every(fn (string $area): bool => MenuItem::isEmpty($area)),
        ]);
    }

    /**
     * Copia el menú de la configuración a la base para poder editarlo.
     *
     * Es el mismo trabajo que `db:seed --class=MenuSeeder`, y no se duplica:
     * la semilla ya sabe no pisar lo que haya, así que pulsar dos veces no
     * rompe nada.
     */
    public function adopt(): RedirectResponse
    {
        (new MenuSeeder)->run();

        return redirect()
            ->route('admin.menu.index')
            ->with('status', __('admin-menu.mensaje.copiado'));
    }

    /* ------------------------------------------------------------------ */
    /* Alta y edición de una entrada                                       */
    /* ------------------------------------------------------------------ */

    public function create(Request $request): View
    {
        $padre = $request->filled('padre')
            ? MenuItem::query()->whereKey($request->integer('padre'))->firstOrFail()
            : null;

        $area = $padre?->area ?? $request->string('area')->toString();

        abort_unless(in_array($area, MenuItem::AREAS, true), 404);

        return view('admin.menu.form', [
            'item' => new MenuItem(['area' => $area, 'parent_id' => $padre?->id, 'is_active' => true]),
            'padre' => $padre,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $padre = $request->filled('padre')
            ? MenuItem::query()->whereKey($request->integer('padre'))->firstOrFail()
            : null;

        $area = $padre?->area ?? $request->string('area')->toString();

        abort_unless(in_array($area, MenuItem::AREAS, true), 404);

        $datos = $this->validated($request, $padre === null);

        MenuItem::create($datos + [
            'area' => $area,
            'parent_id' => $padre?->id,
            // El «key» solo lo llevan los grupos, y se calcula una vez.
            'key' => $padre === null ? MenuItem::freeKey($datos['label']) : null,
            'position' => (int) MenuItem::query()
                ->where('area', $area)
                ->where('parent_id', $padre?->id)
                ->max('position') + 1,
        ]);

        return redirect()
            ->route('admin.menu.index')
            ->with('status', __('admin-menu.mensaje.creada', ['rotulo' => $datos['label']]));
    }

    public function edit(MenuItem $item): View
    {
        return view('admin.menu.form', ['item' => $item, 'padre' => $item->parent]);
    }

    public function update(Request $request, MenuItem $item): RedirectResponse
    {
        $item->update($this->validated($request, $item->isGroup()));

        return redirect()
            ->route('admin.menu.index')
            ->with('status', __('admin-menu.mensaje.guardada', ['rotulo' => $item->label]));
    }

    /**
     * Borra la entrada, y con ella lo que cuelgue.
     *
     * La cascada la hace la clave ajena de la migración: una entrada sin grupo
     * no significa nada y quedaría invisible en esta pantalla.
     */
    public function destroy(MenuItem $item): RedirectResponse
    {
        $rotulo = $item->label;
        $item->delete();

        return redirect()
            ->route('admin.menu.index')
            ->with('status', __('admin-menu.mensaje.borrada', ['rotulo' => $rotulo]));
    }

    /* ------------------------------------------------------------------ */
    /* Orden y visibilidad                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Sube o baja una entrada intercambiándola con su vecina.
     *
     * Una petición por clic, sin JavaScript: el menú se ordena una vez cada
     * varios meses y así la pantalla funciona igual con el teclado, sin guion y
     * en un navegador viejo. Se intercambian las posiciones en vez de
     * renumerar la lista entera para que dos personas editando a la vez no se
     * pisen más allá de esas dos filas.
     */
    public function move(Request $request, MenuItem $item): RedirectResponse
    {
        $arriba = $request->string('direccion')->toString() === 'arriba';

        $vecina = MenuItem::query()
            ->where('area', $item->area)
            ->where('parent_id', $item->parent_id)
            ->where('position', $arriba ? '<' : '>', $item->position)
            ->orderBy('position', $arriba ? 'desc' : 'asc')
            ->first();

        if ($vecina) {
            [$item->position, $vecina->position] = [$vecina->position, $item->position];

            $item->save();
            $vecina->save();
        }

        // El ancla se pega a mano: route() con un array sin clave la metería
        // como parámetro —«?0=%23huv-menu-15»— y no como fragmento, así que la
        // pantalla volvería arriba del todo en cada movimiento.
        return redirect()->to(route('admin.menu.index').'#huv-menu-'.$item->id);
    }

    /** Enciende o apaga una entrada: ocultar del portal sin borrarla. */
    public function toggle(MenuItem $item): RedirectResponse
    {
        $item->update(['is_active' => ! $item->is_active]);

        return redirect()
            ->route('admin.menu.index')
            ->with('status', __(
                $item->is_active ? 'admin-menu.mensaje.visible' : 'admin-menu.mensaje.oculta',
                ['rotulo' => $item->label]
            ));
    }

    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $esGrupo): array
    {
        $datos = $request->validate([
            'label' => ['required', 'string', 'max:120'],

            // Tres destinos posibles y excluyentes. «ninguno» es lo que hace
            // que un grupo sea un grupo: un rótulo del que cuelgan entradas y
            // que no lleva a ninguna parte.
            'destino' => ['required', Rule::in(['interno', 'externo', 'ninguno'])],

            // Interno: una ruta de este portal. LegacyLink decide si ya existe
            // aquí o si todavía hay que servirla del portal anterior, así que
            // vale también para secciones sin migrar.
            'path' => ['nullable', 'required_if:destino,interno', 'string', 'max:255', 'starts_with:/'],

            'url' => ['nullable', 'required_if:destino,externo', 'url', 'max:255'],

            'narrow' => ['boolean'],
            'columns' => ['nullable', Rule::in([2, 3])],
        ], [
            'path.starts_with' => __('admin-menu.error.ruta_barra'),
        ], [
            'label' => __('admin-menu.campo.rotulo'),
            'path' => __('admin-menu.campo.ruta'),
            'url' => __('admin-menu.campo.direccion'),
        ]);

        return [
            'label' => $datos['label'],
            'path' => $datos['destino'] === 'interno' ? $datos['path'] : null,
            'url' => $datos['destino'] === 'externo' ? $datos['url'] : null,
            'narrow' => (bool) ($datos['narrow'] ?? false),
            // Las columnas son cosa de los grupos del menú completo; en una
            // entrada suelta no significan nada.
            'columns' => $esGrupo ? ($datos['columns'] ?? null) : null,
        ];
    }
}
