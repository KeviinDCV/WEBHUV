<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\TopicItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Carga masiva de enlaces desde una hoja de cálculo.
 *
 * Los temas de contratación se alimentan de un listado que ya existe en otro
 * sistema; darlos de alta uno a uno serían cientos de formularios. La hoja lleva
 * tres columnas —nombre, descripción y dirección— y ninguna cabecera, como en el
 * portal actual.
 */
class TopicBulkController extends Controller
{
    /** Tope de filas por carga: evita que un archivo enorme agote la memoria. */
    private const MAX_ROWS = 2000;

    public function create(Topic $topic): View
    {
        return view('admin.topics.bulk', ['topic' => $topic]);
    }

    public function store(Request $request, Topic $topic): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ], [
            'archivo.required' => __('mensajes.validacion.hoja_obligatoria'),
            'archivo.mimes' => __('mensajes.validacion.hoja_formato'),
            'archivo.max' => __('mensajes.validacion.hoja_pesada'),
        ], ['archivo' => __('mensajes.campo.archivo')]);

        [$creados, $problemas] = $this->readRows($request->file('archivo')->getRealPath(), $topic);

        if ($creados === 0 && $problemas !== []) {
            return back()->withErrors(['archivo' => $problemas])->withInput();
        }

        $mensaje = __('mensajes.carga.cargados', ['n' => $creados]);

        if ($problemas !== []) {
            $mensaje .= ' '.__('mensajes.carga.descartadas', ['n' => count($problemas)]);
        }

        return redirect()
            ->to(route('topics.show', $topic).'#huv-listado')
            ->with('status', $mensaje)
            ->with('bulkIssues', $problemas);
    }

    /**
     * Lee la hoja fila a fila.
     *
     * Se lee en flujo y no de golpe: un listado de contratación puede traer
     * miles de filas y cargarlas todas en memoria antes de empezar sería
     * gastarla sin necesidad.
     *
     * @return array{0: int, 1: list<string>}
     */
    private function readRows(string $path, Topic $topic): array
    {
        $reader = new Reader;
        $reader->open($path);

        $creados = 0;
        $problemas = [];
        $numero = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $numero++;

                if ($numero > self::MAX_ROWS) {
                    $problemas[] = __('mensajes.carga.tope', ['n' => self::MAX_ROWS]);
                    break 2;
                }

                $celdas = array_map(
                    fn ($cell) => trim((string) $cell->getValue()),
                    $row->getCells()
                );

                // Filas en blanco al final de la hoja: se saltan sin ruido.
                if (implode('', $celdas) === '') {
                    continue;
                }

                [$nombre, $descripcion, $url] = array_pad($celdas, 3, '');

                if ($nombre === '' || $url === '') {
                    $problemas[] = __('mensajes.carga.fila_incompleta', ['fila' => $numero]);

                    continue;
                }

                if (! filter_var($url, FILTER_VALIDATE_URL) || ! str_starts_with($url, 'http')) {
                    $problemas[] = __('mensajes.carga.fila_direccion', ['fila' => $numero, 'url' => $url]);

                    continue;
                }

                $topic->items()->create([
                    'kind' => TopicItem::KIND_LINK,
                    'title' => mb_substr($nombre, 0, 200),
                    'body' => $descripcion === '' ? null : '<p>'.e($descripcion).'</p>',
                    'source_url' => $url,
                    'published_at' => now(),
                    'modified_at' => now(),
                ]);

                $creados++;
            }
        }

        $reader->close();

        return [$creados, $problemas];
    }
}
