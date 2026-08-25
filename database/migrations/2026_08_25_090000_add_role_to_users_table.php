<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dos roles: operador y administrador.
 *
 * El operador puede todo lo que se podía hacer hasta ahora —editar contenidos,
 * temas, banners, el menú—. El administrador, además, lo que toca a la propia
 * herramienta: dar de alta cuentas y ver las estadísticas de uso.
 *
 * Las cuentas que ya existían pasan a administrador. Es lo correcto y además lo
 * único seguro: eran las únicas que había y con ellas se administra el portal,
 * así que dejarlas en operador habría cerrado la puerta desde dentro, sin nadie
 * capaz de repartir permisos.
 *
 * El valor por omisión, en cambio, es operador: quien cree una cuenta nueva y
 * no diga nada se lleva el permiso menor, no el mayor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default(User::ROLE_OPERATOR)->after('email');
        });

        DB::table('users')->update(['role' => User::ROLE_ADMIN]);
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
