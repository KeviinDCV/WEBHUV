<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Biblioteca de imágenes reutilizables, organizada por categorías.
     *
     * A diferencia de las fotos que se suben dentro de un contenido, estas
     * viven por su cuenta y pueden usarse en varios: por eso el archivo no se
     * borra al quitar la imagen de un contenido concreto.
     */
    public function up(): void
    {
        Schema::create('media_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 80)->unique();
            $table->timestamps();
        });

        Schema::create('library_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('alt', 250);
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });

        Schema::table('content_media', function (Blueprint $table): void {
            // Cuando la foto viene de la biblioteca, el archivo no es del
            // contenido: al desvincularla no debe borrarse del disco.
            $table->foreignId('library_image_id')->nullable()->after('content_id')
                ->constrained()->cascadeOnDelete();
        });

        Schema::table('contents', function (Blueprint $table): void {
            // Etapa de participación ciudadana con la que se relaciona el
            // contenido (Ley 1757 de 2015). Nula: contenido sin participación.
            $table->string('participation', 80)->nullable()->after('show_in_feed');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table): void {
            $table->dropColumn('participation');
        });

        Schema::table('content_media', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('library_image_id');
        });

        Schema::dropIfExists('library_images');
        Schema::dropIfExists('media_categories');
    }
};
