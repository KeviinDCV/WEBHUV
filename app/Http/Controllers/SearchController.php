<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\TopicItem;
use App\Support\SiteSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * El buscador del portal.
 *
 * Todo viaja en la dirección —el término, el tipo y las fechas—, así que una
 * búsqueda se puede compartir por correo y la página funciona sin JavaScript,
 * igual que el listado paginado de un tema.
 */
class SearchController extends Controller
{
    /**
     * Los tipos que se pueden filtrar, tal y como se escriben en la dirección.
     *
     * En castellano y no con el nombre de la clase: la dirección la lee un ser
     * humano, y el nombre de una clase de PHP no tiene por qué salir a la calle.
     */
    private const TYPES = [
        'contenidos' => Content::class,
        'temas' => TopicItem::class,
    ];

    public function __invoke(Request $request): View
    {
        $terms = trim((string) $request->string('q'));
        $type = self::TYPES[(string) $request->string('tipo')] ?? null;

        $search = new SiteSearch(
            $terms,
            $type,
            $this->date($request, 'desde'),
            $this->date($request, 'hasta'),
        );

        return view('buscar', [
            'terms' => $terms,
            'tipo' => (string) $request->string('tipo'),
            'desde' => (string) $request->string('desde'),
            'hasta' => (string) $request->string('hasta'),
            'buscable' => $search->isSearchable(),
            'results' => $search->paginate(max(1, $request->integer('page', 1))),
        ]);
    }

    /**
     * Una fecha del formulario, o nada.
     *
     * Lo que llega de fuera puede ser cualquier cosa: una fecha ilegible se
     * ignora en vez de reventar la página. Sin esto, `?desde=ayer` era un error
     * quinientos en una dirección que cualquiera puede escribir.
     */
    private function date(Request $request, string $field): ?Carbon
    {
        $value = trim((string) $request->string($field));

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
