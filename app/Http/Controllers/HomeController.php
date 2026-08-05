<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\BannerController;
use App\Models\Banner;
use App\Models\Setting;
use App\Support\EventCalendar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Página de inicio institucional.
     *
     * El contenido vive en config/huv.php mientras no exista administración
     * de contenidos; las vistas lo leen directamente vía config(). Lo único
     * que se calcula aquí es el periodo visible de la agenda, porque depende
     * de la fecha actual y de la navegación del usuario.
     */
    public function index(Request $request): View
    {
        return view('home', [
            'banners' => Banner::active()->ordered()->get(),
            'rotation' => (int) Setting::get(BannerController::ROTATION_KEY, 10),
            'calendar' => EventCalendar::make(
                config('huv.events.items'),
                $request->query('vista'),
                $request->query('periodo', 0),
            ),
        ]);
    }
}
