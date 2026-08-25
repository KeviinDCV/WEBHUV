<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El menú del portal, para que se pueda editar sin tocar código.
 *
 * Hasta ahora la navegación vivía en config/huv.php: añadir una sección era un
 * cambio de código y un despliegue, así que en la práctica el hospital no podía
 * hacerlo. La configuración se queda ahí como semilla y como red —ver
 * MenuItem::tree()—, pero manda esta tabla en cuanto tiene filas.
 *
 * Una sola tabla y no dos: un grupo se distingue de una entrada por no tener
 * padre, y así el editor es un único listado ordenable en vez de dos pantallas
 * que hay que mantener a la par.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();

            // Al borrar un grupo se van sus entradas: sueltas no significan
            // nada y quedarían invisibles en el editor.
            $table->foreignId('parent_id')->nullable()
                ->constrained('menu_items')->cascadeOnDelete();

            // 'bar' es la barra de la cabecera; 'mega', el menú del botón ☰.
            $table->string('area', 8);

            // Identificador estable para los ids del DOM y el estado de Alpine
            // («huv-menu-atencion», «huv-tabpanel-documentos»). No cambia
            // aunque se renombre el rótulo: si cambiara, se romperían el
            // aria-controls del desplegable y el del mapa del sitio.
            $table->string('key')->nullable()->unique();

            // El rótulo en español. La traducción de las entradas que vienen
            // del portal está escrita a mano y se encuentra por 'i18n'; las que
            // se creen desde el editor no la tienen todavía.
            $table->string('label');
            $table->string('i18n')->nullable();

            // El destino, uno de los dos: 'path' es interno y lo resuelve
            // LegacyLink contra este portal o el anterior según lo migrado;
            // 'url' es de fuera y se abre en otra pestaña. Un grupo no tiene
            // ninguno de los dos.
            $table->string('path')->nullable();
            $table->string('url')->nullable();

            // Columnas del panel en el menú completo: «Entidades relacionadas»
            // va a tres porque son sesenta y seis.
            $table->unsignedTinyInteger('columns')->nullable();

            // El ancho recortado de la barra, para los dos rótulos largos.
            $table->boolean('narrow')->default(false);

            $table->unsignedInteger('position')->default(0);

            // Ocultar sin borrar: es lo que el portal de origen pinta en gris.
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['area', 'parent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
