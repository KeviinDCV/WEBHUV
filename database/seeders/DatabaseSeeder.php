<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * No se crean usuarios aquí a propósito: las cuentas del portal se dan de
     * alta con `php artisan huv:usuario`, que pide la contraseña de forma
     * oculta. Un usuario sembrado con contraseña conocida en el repositorio
     * sería una puerta abierta si alguna vez se ejecuta en producción.
     */
    public function run(): void
    {
        $this->call([ContentSeeder::class, ShortcutSeeder::class]);
    }
}
