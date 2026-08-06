<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bloques de contenidos de la portada.
     *
     * Un bloque —«Noticias», por ejemplo— decide de qué categorías se nutre,
     * con qué rótulo, en qué orden y con qué color. La tabla `contents` guarda
     * el qué; esta guarda el cómo se presenta.
     */
    public function up(): void
    {
        Schema::create('content_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 40)->unique();   // identificador estable en las vistas
            $table->string('name', 30);
            $table->string('sort', 20)->default('recent'); // recent | oldest
            $table->boolean('show_title')->default(true);
            $table->string('theme', 20)->default('navy');
            $table->unsignedTinyInteger('position')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('content_block_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_block_id')->constrained()->cascadeOnDelete();
            $table->string('category', 60);
            $table->string('title', 150);
            $table->boolean('hide_in_feed')->default(false);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();

            $table->index(['content_block_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_block_sections');
        Schema::dropIfExists('content_blocks');
    }
};
