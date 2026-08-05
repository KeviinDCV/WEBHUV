<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajustes del portal en clave/valor.
     *
     * Nace para la duración de rotación del banner, pero sirve para cualquier
     * preferencia editable que no justifique una tabla propia.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->string('key', 100)->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
