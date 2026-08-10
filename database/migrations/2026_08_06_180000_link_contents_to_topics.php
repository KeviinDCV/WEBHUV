<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * «Noticias» no es un tema más: es la portada.
     *
     * En el portal, /tema/noticias y el bloque de noticias del inicio muestran
     * lo mismo —los 68 contenidos del tema salen marcados para la portada—, así
     * que importarlo a `topic_items` dejaría cada noticia por duplicado y dos
     * sitios donde editarla.
     *
     * En vez de eso, el tema declara de qué categoría de `contents` se nutre y
     * su listado consulta esa tabla. El tema sigue existiendo para dar nombre,
     * dirección y categorías a la página.
     */
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table): void {
            $table->string('content_category', 60)->nullable()->after('legacy_template_type');
        });

        Schema::table('contents', function (Blueprint $table): void {
            // Para reimportar sin duplicar, igual que en `topic_items`.
            $table->unsignedInteger('legacy_content_id')->nullable()->unique()->after('id');
            // La fecha que encabeza la ficha; `updated_at` no sirve porque una
            // reimportación la sobreescribiría.
            $table->timestamp('modified_at')->nullable()->after('published_at');
        });

        // Las etiquetas del tema —«Educación», «Noticias»— aplicadas a los
        // contenidos, para que el listado pueda ofrecer sus mismos filtros.
        Schema::create('content_topic_category', function (Blueprint $table): void {
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_category_id')->constrained()->cascadeOnDelete();

            $table->primary(['content_id', 'topic_category_id']);
            $table->index('topic_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_topic_category');

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropUnique(['legacy_content_id']);
        });

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropColumn(['legacy_content_id', 'modified_at']);
        });

        Schema::table('topics', function (Blueprint $table): void {
            $table->dropColumn('content_category');
        });
    }
};
