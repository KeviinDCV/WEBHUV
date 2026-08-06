<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha final de visualización.
     *
     * Pasada esa fecha el contenido deja de mostrarse al público, sin
     * necesidad de acordarse de retirarlo a mano. Nula: se muestra
     * indefinidamente.
     */
    public function up(): void
    {
        Schema::table('contents', function (Blueprint $table): void {
            $table->timestamp('expires_at')->nullable()->index()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table): void {
            $table->dropColumn('expires_at');
        });
    }
};
