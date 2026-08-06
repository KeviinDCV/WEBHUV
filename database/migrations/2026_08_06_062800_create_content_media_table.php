<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Medios de un contenido: fotos, vídeo de YouTube y archivos adjuntos.
     *
     * Sustituye a las columnas image_path/image_alt de `contents`: una sola
     * fuente para el material del contenido, con una foto marcada como
     * principal en lugar de dos sitios donde guardar imágenes.
     */
    public function up(): void
    {
        Schema::create('content_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();

            $table->string('type', 10); // image | video | file

            $table->string('path')->nullable();       // imagen o archivo en disco
            $table->string('url', 2048)->nullable();  // vídeo de YouTube

            // Descripción accesible; obligatoria en imágenes (WCAG 1.1.1).
            $table->string('alt', 250)->nullable();

            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->unsignedTinyInteger('position')->default(0);
            $table->boolean('is_main')->default(false);

            $table->timestamps();

            $table->index(['content_id', 'type']);
        });

        // Traslada la imagen que ya tuviera cada contenido.
        DB::table('contents')
            ->whereNotNull('image_path')
            ->orderBy('id')
            ->each(function (object $content): void {
                DB::table('content_media')->insert([
                    'content_id' => $content->id,
                    'type' => 'image',
                    'path' => $content->image_path,
                    'alt' => $content->image_alt,
                    'position' => 1,
                    'is_main' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropColumn(['image_path', 'image_alt']);
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table): void {
            $table->string('image_path')->nullable();
            $table->string('image_alt', 250)->nullable();
        });

        Schema::dropIfExists('content_media');
    }
};
