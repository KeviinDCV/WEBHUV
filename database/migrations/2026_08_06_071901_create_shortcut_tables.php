<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Barras de accesos directos de la portada.
     *
     * Cada barra es un bloque con nombre, tema de color y hasta cinco accesos.
     * La portada muestra las barras una debajo de otra.
     */
    public function up(): void
    {
        Schema::create('shortcut_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 30);
            $table->string('theme', 20)->default('navy');
            $table->unsignedTinyInteger('position')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('shortcuts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shortcut_block_id')->constrained()->cascadeOnDelete();
            $table->string('label', 40);
            $table->string('url', 2048);
            $table->string('icon', 40)->default('info');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();

            $table->index(['shortcut_block_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortcuts');
        Schema::dropIfExists('shortcut_blocks');
    }
};
