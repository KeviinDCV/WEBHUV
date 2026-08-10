<?php

use App\Support\CommentWall;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La participación de una noticia pasa a expresarse como en el portal.
     *
     * `participation` guardaba una de las seis etapas de la Ley 1757, que es una
     * clasificación nuestra: el portal institucional solo distingue si el
     * contenido abre su muro al público, lo abre en privado o no lo abre, y es
     * eso lo que enciende el botón «Participa».
     *
     * Se unifica con `topic_items.comment_wall` para que el mismo editor y la
     * misma insignia sirvan a los dos.
     */
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table): void {
            $table->unsignedTinyInteger('comment_wall')
                ->default(CommentWall::NINGUNA)
                ->after('show_in_feed');
        });

        // Lo que tuviera una etapa asignada estaba abierto a participación; la
        // etapa concreta no tiene equivalente y se pierde a propósito.
        DB::table('contents')
            ->whereNotNull('participation')
            ->where('participation', '<>', '')
            ->update(['comment_wall' => CommentWall::PUBLICA]);

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropColumn('participation');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table): void {
            $table->string('participation', 80)->nullable()->after('show_in_feed');
        });

        // Sin correspondencia real: se restituye la etapa más genérica para lo
        // que estuviera abierto.
        DB::table('contents')
            ->where('comment_wall', '<>', CommentWall::NINGUNA)
            ->update(['participation' => 'Consulta ciudadana']);

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropColumn('comment_wall');
        });
    }
};
