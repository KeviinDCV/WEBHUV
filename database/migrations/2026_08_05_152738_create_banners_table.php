<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table): void {
            $table->id();

            // Orden de aparición en el carrusel. Se reordena desde el listado.
            $table->unsignedTinyInteger('position')->default(0)->index();

            $table->string('media_type', 20)->default('image');
            $table->string('image_path')->nullable();

            // Velo de color sobre la imagen, para que el texto se lea encima.
            $table->string('filter_color', 7)->default('#000000');
            $table->unsignedTinyInteger('filter_opacity')->default(0); // 0–100

            $table->string('title', 90)->nullable();
            $table->string('title_color', 7)->default('#FFFFFF');
            $table->string('title_background', 7)->nullable();
            $table->string('title_font', 40)->default('Montserrat');
            $table->boolean('title_bold')->default(true);
            $table->boolean('title_italic')->default(false);

            $table->string('subtitle', 140)->nullable();
            $table->string('subtitle_color', 7)->default('#FFFFFF');
            $table->string('subtitle_background', 7)->nullable();
            $table->string('subtitle_font', 40)->default('Montserrat');
            $table->boolean('subtitle_bold')->default(false);
            $table->boolean('subtitle_italic')->default(false);

            $table->string('alignment', 10)->default('left');

            // Obligatorio: sin descripción, el banner es inaccesible para quien
            // usa lector de pantalla (WCAG 1.1.1).
            $table->string('alt_text', 250);

            $table->string('link', 2048)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
