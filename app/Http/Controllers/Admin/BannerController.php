<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Administración de los banners de la portada.
 */
class BannerController extends Controller
{
    /** Opciones de rotación automática, en segundos. */
    public const ROTATION_OPTIONS = [5, 7, 10, 15, 20, 30];

    public const ROTATION_KEY = 'banners.rotation_seconds';

    public function index(): View
    {
        return view('admin.banners.index', [
            'banners' => Banner::ordered()->get(),
            'rotation' => (int) Setting::get(self::ROTATION_KEY, 10),
        ]);
    }

    public function create(): View
    {
        $this->ensureThereIsRoom();

        return view('admin.banners.form', ['banner' => new Banner([
            'filter_color' => '#000000',
            'filter_opacity' => 0,
            'title_color' => '#FFFFFF',
            'subtitle_color' => '#FFFFFF',
            'title_font' => 'Montserrat',
            'subtitle_font' => 'Montserrat',
            'title_bold' => true,
            'alignment' => 'left',
        ])]);
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        $this->ensureThereIsRoom();

        $banner = new Banner($request->safe()->except('image'));
        $banner->position = (int) Banner::max('position') + 1;
        $banner->image_path = $request->file('image')->store('banners', 'public');
        $banner->save();

        // Al guardar se vuelve a la portada, que es donde se comprueba el
        // resultado. Para seguir administrando está el botón «Editar» sobre
        // el propio banner.
        return redirect()
            ->route('home')
            ->with('status', 'Banner agregado correctamente.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', ['banner' => $banner]);
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $banner->fill($request->safe()->except('image'));

        if ($request->hasFile('image')) {
            // Se borra la anterior para no dejar archivos huérfanos en disco.
            $banner->deleteImage();
            $banner->image_path = $request->file('image')->store('banners', 'public');
        }

        $banner->save();

        return redirect()
            ->route('home')
            ->with('status', 'Banner actualizado correctamente.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()
            ->route('admin.banners.index')
            ->with('status', 'Banner eliminado.');
    }

    /**
     * Guarda el orden del carrusel y la duración de la rotación.
     */
    public function arrange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['array'],
            'order.*' => ['integer', 'exists:banners,id'],
            'rotation' => ['required', 'integer', Rule::in(self::ROTATION_OPTIONS)],
        ], [
            'rotation.in' => 'La duración de rotación seleccionada no es válida.',
        ]);

        foreach ($validated['order'] ?? [] as $position => $id) {
            Banner::whereKey($id)->update(['position' => $position + 1]);
        }

        Setting::put(self::ROTATION_KEY, $validated['rotation']);

        return redirect()
            ->route('home')
            ->with('status', 'Cambios guardados.');
    }

    /**
     * @throws ValidationException
     */
    private function ensureThereIsRoom(): void
    {
        if (Banner::count() >= Banner::MAX) {
            throw ValidationException::withMessages([
                'banner' => 'Ya hay '.Banner::MAX.' banners publicados, el máximo permitido. '
                    .'Elimine uno antes de agregar otro.',
            ]);
        }
    }
}
