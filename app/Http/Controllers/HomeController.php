<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Página de inicio institucional.
     *
     * El contenido vive en config/huv.php mientras no exista administración
     * de contenidos; las vistas lo leen directamente vía config().
     */
    public function index(): View
    {
        return view('home');
    }
}
