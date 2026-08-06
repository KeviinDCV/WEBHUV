<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contenidos publicados: noticias, comunicados y notificaciones.
     *
     * Una sola tabla alimenta el bloque de Noticias y el muro de contenidos de
     * la portada. Tenerlos separados obligaría a mantener el mismo texto en dos
     * sitios, que se desincronizarían al primer cambio.
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table): void {
            $table->id();

            $table->string('title', 150);
            $table->string('slug', 180)->unique();
            $table->string('category', 60)->index();

            $table->string('excerpt', 400)->nullable();
            $table->longText('body')->nullable();

            $table->string('image_path')->nullable();
            // Descripción de la imagen: obligatoria cuando hay imagen (WCAG 1.1.1).
            $table->string('image_alt', 250)->nullable();

            // Enlace externo, para contenidos que viven fuera del portal.
            $table->string('link', 2048)->nullable();

            // Nula cuando se marca «sin fecha de visualización».
            $table->timestamp('published_at')->nullable()->index();

            // La destacada es la que ocupa el espacio grande del bloque.
            $table->boolean('is_featured')->default(false)->index();

            // Inactiva: no se muestra en ninguna parte del sitio.
            $table->boolean('is_active')->default(true)->index();

            // Oculta: activa, pero fuera de la portada.
            $table->boolean('is_hidden')->default(false);

            // Fuera del muro de contenidos, pero sigue en el bloque de Noticias.
            $table->boolean('show_in_feed')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
