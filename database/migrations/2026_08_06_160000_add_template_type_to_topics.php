<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cómo se ordena el listado del tema.
     *
     * El portal distingue dos plantillas: la corriente, que ordena por fecha y
     * ofrece «Ordenar por», y la ordenable, en la que quien edita coloca los
     * contenidos a mano y esas opciones no se muestran. «Control Interno» es de
     * las segundas: sus fichas no salen en orden cronológico.
     */
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table): void {
            $table->string('legacy_template_type', 40)->nullable()->after('legacy_content_types');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table): void {
            $table->dropColumn('legacy_template_type');
        });
    }
};
