<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cómo se hace un trámite: presencial o en línea, con costo o sin él, y cuánto
 * tarda.
 *
 * Son los tres datos que el portal publica al lado de cada trámite y lo único
 * que distingue una fila de otra de un vistazo. Llegan en la misma lista de
 * atributos que el lugar de un evento —«ProcedureTypeID», «ProcedureCostType»,
 * «ProcedureValue», «ProcedureTime»—, aparte del cuerpo.
 *
 * `ProcedureUrl` no estrena columna: el origen la deja vacía en los diez
 * trámites, incluido el que es en línea. La dirección que sí usa es la ficha de
 * gov.co, y esa va en `source_url`, como el destino de cualquier enlace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topic_items', function (Blueprint $table): void {
            // 1 en línea, 2 presencial: los mismos números del origen, para que
            // una reimportación no tenga que traducir nada.
            $table->unsignedTinyInteger('procedure_type')->nullable()->after('event_host');

            // 0 sin costo, 1 con costo, 2 costo exacto.
            $table->unsignedTinyInteger('procedure_cost_type')->nullable()->after('procedure_type');

            // Texto y no un decimal: el origen lo publica como cadena y hoy
            // siempre vale «0». Guardarlo como número obligaría a decidir la
            // moneda y los decimales sin un solo caso real que lo respalde.
            $table->string('procedure_cost')->nullable()->after('procedure_cost_type');

            // «3 Días Hábiles», «Obtención inmediata», «30 Minutos». No es una
            // duración que se pueda calcular: es lo que escribe quien publica.
            $table->string('procedure_time')->nullable()->after('procedure_cost');
        });
    }

    public function down(): void
    {
        Schema::table('topic_items', function (Blueprint $table): void {
            $table->dropColumn([
                'procedure_type',
                'procedure_cost_type',
                'procedure_cost',
                'procedure_time',
            ]);
        });
    }
};
