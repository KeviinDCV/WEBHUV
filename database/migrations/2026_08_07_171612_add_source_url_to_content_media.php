<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De dónde salió cada medio importado.
 *
 * Sin este dato la importación no puede converger: al reencontrar un medio que
 * ya existe no había forma de saber si el portal había publicado otro archivo
 * en su sitio, así que se dejaba el viejo para siempre. Un documento acababa
 * con dos políticas contradictorias —su archivo principal se refrescaba y sus
 * adjuntos no— y nadie se enteraba.
 *
 * No se reutiliza la columna `url`, que guarda la dirección de YouTube de un
 * vídeo: son dos cosas distintas y mezclarlas confunde el día que haya que
 * mirarlas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_media', function (Blueprint $table) {
            $table->string('source_url', 2048)->nullable()->after('legacy_file_id');
        });
    }

    public function down(): void
    {
        Schema::table('content_media', function (Blueprint $table) {
            $table->dropColumn('source_url');
        });
    }
};
