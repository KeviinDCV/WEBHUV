<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un elemento puede llevar varias categorías del tema.
     *
     * El programa de transparencia y ética empresarial está a la vez en
     * «Programa PTEE» y en «2025». La clave foránea única que servía para los
     * documentos perdía una de las dos.
     */
    public function up(): void
    {
        Schema::create('topic_category_topic_item', function (Blueprint $table): void {
            $table->foreignId('topic_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_category_id')->constrained()->cascadeOnDelete();

            $table->primary(['topic_item_id', 'topic_category_id']);
            // El listado filtra por categoría: sin este índice es un escaneo.
            $table->index('topic_category_id');
        });

        // La categoría única que ya tenía cada documento importado.
        DB::table('topic_items')
            ->whereNotNull('topic_category_id')
            ->orderBy('id')
            ->each(fn (object $row) => DB::table('topic_category_topic_item')->insert([
                'topic_item_id' => $row->id,
                'topic_category_id' => $row->topic_category_id,
            ]));

        // La clave foránea se soltó en la migración anterior, así que soltar la
        // columna es seguro en los dos motores.
        Schema::table('topic_items', function (Blueprint $table): void {
            $table->dropColumn('topic_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('topic_items', function (Blueprint $table): void {
            $table->foreignId('topic_category_id')->nullable()->constrained()->nullOnDelete();
        });

        // Solo se puede recuperar una categoría por elemento: es justo la
        // limitación que esta migración vino a quitar.
        DB::table('topic_category_topic_item')
            ->orderBy('topic_item_id')
            ->each(fn (object $row) => DB::table('topic_items')
                ->where('id', $row->topic_item_id)
                ->update(['topic_category_id' => $row->topic_category_id]));

        Schema::dropIfExists('topic_category_topic_item');
    }
};
