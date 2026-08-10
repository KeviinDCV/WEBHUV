<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table): void {
            // Los tipos de contenido que el tema declara en el portal de origen,
            // tal cual llegan. No se reducen a uno solo: 22 de los 62 temas
            // declaran varios y 14 los mezclan de verdad —«ciau» tiene
            // documentos, artículos, enlaces y avisos en el mismo listado—.
            // De aquí salen las pestañas de orden y qué se puede dar de alta.
            $table->json('legacy_content_types')->nullable()->after('description');

            // Plantilla que precarga el editor al crear un contenido en el tema.
            // Solo la devuelve el detalle del tema en el portal de origen; en el
            // listado llega siempre vacía.
            $table->text('content_template')->nullable()->after('legacy_content_types');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table): void {
            $table->dropColumn(['legacy_content_types', 'content_template']);
        });
    }
};
