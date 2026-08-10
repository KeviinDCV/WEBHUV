<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un tema no guarda solo documentos.
     *
     * En el portal de origen, un tema contiene documentos, artículos, enlaces y
     * avisos, y 14 de los 62 temas los mezclan de verdad en el mismo listado. La
     * tabla `documents` pasa a llamarse `topic_items` y cada fila dice qué es.
     *
     * Se renombra en lugar de crear una tabla nueva para no volver a importar
     * los documentos ya traídos ni a descargar sus archivos.
     *
     * Nota para migraciones futuras: MySQL conserva los nombres de índice
     * antiguos tras un RENAME (`documents_topic_id_slug_unique`, etc.). Es
     * inocuo salvo para quien intente adivinarlos.
     */
    public function up(): void
    {
        // La clave foránea se suelta ANTES del renombrado: Laravel deriva su
        // nombre del nombre actual de la tabla, y MySQL conserva el viejo. Si se
        // soltara después, buscaría `topic_items_…_foreign`, que no existe.
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign(['topic_category_id']);
        });

        Schema::rename('documents', 'topic_items');

        Schema::table('topic_items', function (Blueprint $table): void {
            // Qué es cada elemento. Va en la fila y no en el tema porque un tema
            // mixto guarda documentos y artículos en el mismo listado y con el
            // mismo orden. Lo ya importado es documental: ese es el valor por
            // defecto, y también el que se aplica cuando el origen no lo dice.
            $table->string('kind', 20)->default('documento')->after('topic_id')->index();

            // Fecha final de visualización, propia de los artículos. Los
            // documentos no la usan: nula no excluye nada.
            $table->timestamp('expires_at')->nullable()->after('published_at')->index();

            // Muro de participación del origen: 0 público, 1 privado, 2 sin
            // participación. No son las etapas de la Ley 1757 que guarda
            // `contents.participation`, pese al parecido; de ahí el otro nombre.
            $table->unsignedTinyInteger('comment_wall')->default(2)->after('is_hidden');

            $table->unsignedInteger('legacy_display_order')->nullable()->after('legacy_content_id');

            // El origen marca qué contenidos salen en su portada. Aquí no
            // alimenta nada, pero perderlo al importar sería irreversible.
            $table->boolean('legacy_show_on_home')->nullable()->after('legacy_display_order');
        });

        // El editor de contenidos se reutiliza tal cual y su campo se llama
        // `body`. Renombrar la columna evita un accesor mágico con el que
        // `where('body', …)` fallaría con un error de SQL incomprensible.
        Schema::table('topic_items', function (Blueprint $table): void {
            $table->renameColumn('description', 'body');
        });
    }

    public function down(): void
    {
        Schema::table('topic_items', function (Blueprint $table): void {
            $table->renameColumn('body', 'description');
        });

        // Los índices se sueltan antes que sus columnas: SQLite reconstruye la
        // tabla al soltar una columna y aborta si algún índice todavía la nombra.
        Schema::table('topic_items', function (Blueprint $table): void {
            $table->dropIndex(['kind']);
            $table->dropIndex(['expires_at']);
        });

        Schema::table('topic_items', function (Blueprint $table): void {
            $table->dropColumn([
                'kind', 'expires_at', 'comment_wall', 'legacy_display_order', 'legacy_show_on_home',
            ]);
        });

        // La migración siguiente devolvió la columna con su clave foránea, y esa
        // clave lleva el nombre de la tabla ACTUAL. MySQL no renombra las
        // restricciones al renombrar la tabla, así que si no se suelta ahora la
        // tabla acabaría con dos claves foráneas sobre la misma columna y
        // volver a migrar sería imposible.
        Schema::table('topic_items', function (Blueprint $table): void {
            $table->dropForeign(['topic_category_id']);
        });

        Schema::rename('topic_items', 'documents');

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreign('topic_category_id')->references('id')->on('topic_categories')->nullOnDelete();
        });
    }
};
