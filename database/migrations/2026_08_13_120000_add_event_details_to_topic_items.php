<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lugar y organizador de un evento.
 *
 * El portal guarda estos dos datos aparte del cuerpo, en una lista de atributos
 * —«EventLocation» y «EventHost»—, y hoy no los enseña en ninguna parte: quien
 * escribió la invitación repitió el auditorio dentro del texto. Se traen igual,
 * porque son datos del contenido y no del texto, y porque «Calendario de
 * actividades» son ciento cuarenta y un eventos que sí los necesitarán.
 *
 * La fecha del evento no estrena columna: va en `opens_at`, la misma que usa
 * una convocatoria para su apertura. Las dos responden a «desde cuándo».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topic_items', function (Blueprint $table): void {
            $table->string('event_location')->nullable()->after('closes_at');
            $table->string('event_host')->nullable()->after('event_location');
        });
    }

    public function down(): void
    {
        Schema::table('topic_items', function (Blueprint $table): void {
            $table->dropColumn(['event_location', 'event_host']);
        });
    }
};
