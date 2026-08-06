<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Temas documentales —Presupuesto, Planes, Contratación…— y sus documentos.
     *
     * No se reutiliza `contents` porque un documento tiene otra forma: un
     * archivo, una fecha de expedición y categorías propias del tema, en lugar
     * de cuerpo, galería y las categorías fijas de las noticias.
     *
     * Las columnas `legacy_*` guardan los identificadores del portal actual:
     * permiten reimportar sin duplicar y saber de dónde vino cada cosa.
     */
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('description', 400)->nullable();
            $table->unsignedInteger('legacy_tag_id')->nullable()->unique();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('topic_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->unsignedInteger('legacy_label_id')->nullable();
            $table->timestamps();

            $table->unique(['topic_id', 'slug']);
        });

        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title', 250);
            $table->string('slug', 280);
            $table->text('description')->nullable();

            // Fecha de expedición del documento, distinta de la de publicación.
            $table->timestamp('issued_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            // Última modificación del contenido: la ficha la muestra junto a la
            // de creación, y no puede ser `updated_at` porque una reimportación
            // la sobreescribiría.
            $table->timestamp('modified_at')->nullable();

            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_extension', 10)->nullable();

            // Enlace al archivo original mientras no se haya descargado.
            $table->string('source_url', 2048)->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_hidden')->default(false);

            $table->unsignedInteger('legacy_content_id')->nullable()->unique();

            $table->timestamps();

            $table->unique(['topic_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('topic_categories');
        Schema::dropIfExists('topics');
    }
};
