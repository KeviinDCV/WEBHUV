<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traducciones del contenido.
 *
 * El contenido llega del portal en español y no existe versión en inglés. Se
 * traduce con la API de Google y se guarda aquí, en una tabla aparte y no en
 * columnas «title_en», «body_en»… de cada modelo: son cuatro modelos con campos
 * distintos, y cada idioma nuevo obligaría a otra migración por tabla.
 *
 * `source_hash` es la pieza que hace esto sostenible. Guarda la huella del
 * texto español que se tradujo, así que una reimportación —que reescribe el
 * cuerpo de cada contenido en cada pasada— no obliga a pagar por traducir otra
 * vez lo que no ha cambiado: se compara la huella y solo se manda lo distinto.
 * Sin eso, cada `huv:importar` costaría los 995.000 caracteres enteros.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->morphs('translatable');
            $table->string('locale', 5);
            $table->string('field', 40);
            $table->longText('value');

            // sha1 del texto de origen: 40 caracteres exactos.
            $table->char('source_hash', 40);

            $table->timestamps();

            // Una sola traducción por campo, idioma y contenido. Es también el
            // índice con el que se leen: la ficha pide sus campos de golpe.
            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field'], 'traduccion_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
