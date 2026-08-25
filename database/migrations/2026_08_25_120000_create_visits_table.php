<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las visitas al portal, para poder decir cuánta gente entra.
 *
 * Se guarda lo mínimo que responde a la pregunta y nada más: qué página, qué
 * día, y un identificador de visitante que solo vale DENTRO de ese día.
 *
 * No hay IP, ni navegador, ni cookie nueva, ni nada que permita saber quién es
 * nadie. El identificador es un resumen de la sesión mezclado con la fecha y
 * con la clave de la aplicación: dos visitas del mismo navegador el mismo día
 * caen en el mismo valor —que es lo que permite contar personas y no páginas—
 * y al día siguiente ese mismo navegador es otro valor distinto, así que no se
 * puede seguir a nadie de un día para otro ni deshacer el resumen.
 *
 * Los índices están puestos para las tres preguntas que hace la pantalla:
 * cuántos visitantes distintos hubo cada día, cuántas páginas se vieron, y
 * cuáles fueron las más visitadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table): void {
            $table->id();

            // Resumen de 32 caracteres: para contar visitantes distintos de un
            // día sobra, y ocupa la mitad que el resumen entero.
            $table->char('visitor', 32);

            $table->string('path', 255);

            // La fecha suelta, además del instante: agrupar por día sin tener
            // que aplicar una función a cada fila es lo que hace que el índice
            // sirva de algo.
            $table->date('visited_on');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['visited_on', 'visitor']);
            $table->index(['visited_on', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
