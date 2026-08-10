<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Apertura y cierre de una convocatoria.
 *
 * Columnas propias y no las que ya había, porque significan otra cosa:
 *
 * - `expires_at` quiere decir «a partir de aquí deja de verse». La fecha de
 *   cierre de una convocatoria no es eso: el portal publica las de 2023, todas
 *   cerradas hace años, junto a las de 2026. Meterla ahí habría escondido
 *   cuarenta y siete de las cincuenta y dos sin que nada lo dijera.
 *
 * - `issued_at` es la fecha de expedición de un documento, y así se rotula en
 *   la ficha. La apertura de una convocatoria no se rotula así, ni se publica:
 *   el portal solo la enseña en el editor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topic_items', function (Blueprint $table) {
            $table->dateTime('opens_at')->nullable()->after('issued_at');
            $table->dateTime('closes_at')->nullable()->after('opens_at');
        });
    }

    public function down(): void
    {
        Schema::table('topic_items', function (Blueprint $table) {
            $table->dropColumn(['opens_at', 'closes_at']);
        });
    }
};
