<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quién organiza el evento.
 *
 * El formulario del portal lo pide justo debajo del título y antes del lugar,
 * con setenta caracteres, y no es un adorno: en la agenda del hospital casi
 * todo lo convoca un servicio concreto —Banco de Sangre, Docencia, Comité de
 * Ética— y sin ese dato el evento no dice a quién preguntarle.
 *
 * Es el mismo dato que la importación ya guarda para los eventos que vienen del
 * portal como contenido de un tema, donde llega en el atributo «EventHost».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('host', 70)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('host');
        });
    }
};
