<?php

use App\Models\ContentMedia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los artículos de un tema tienen fotos, vídeo y adjuntos, igual que las
     * noticias. En lugar de duplicar la tabla de medios y su editor, la misma
     * tabla admite dos dueños posibles.
     *
     * Dos claves foráneas anulables en lugar de una relación polimórfica: así la
     * base de datos sigue garantizando que el medio apunta a algo que existe y
     * sigue borrándose en cascada. La regla de «exactamente un dueño» se
     * comprueba en el modelo (ver App\Models\ContentMedia).
     */
    public function up(): void
    {
        Schema::table('content_media', function (Blueprint $table): void {
            $table->foreignId('topic_item_id')->nullable()->after('content_id')
                ->constrained('topic_items')->cascadeOnDelete();

            // Identificador del archivo en el portal de origen: reimportar un
            // tema no debe volver a descargar las imágenes ni duplicar adjuntos.
            $table->unsignedInteger('legacy_file_id')->nullable()->after('topic_item_id');

            $table->index(['topic_item_id', 'type']);
        });

        // En tres pasos a propósito: cambiar una columna que tiene una clave
        // foránea encima es el punto más frágil de toda la migración.
        Schema::table('content_media', fn (Blueprint $table) => $table->dropForeign(['content_id']));
        Schema::table('content_media', fn (Blueprint $table) => $table->unsignedBigInteger('content_id')->nullable()->change());
        Schema::table('content_media', fn (Blueprint $table) => $table->foreign('content_id')
            ->references('id')->on('contents')->cascadeOnDelete());
    }

    public function down(): void
    {
        // Los medios que pertenecían a un elemento de tema se van con la
        // columna: dejarlos sin dueño los volvería inalcanzables —ninguna
        // relación los devuelve— y sus archivos se quedarían en disco para
        // siempre. Se borran con Eloquent para que cada uno limpie el suyo.
        ContentMedia::whereNotNull('topic_item_id')->get()->each->delete();

        // La clave foránea ANTES que el índice: InnoDB reutiliza este índice
        // como soporte de la restricción y se niega a soltarlo mientras la
        // restricción siga viva. En SQLite da igual, porque reconstruye la
        // tabla entera; en MySQL, al revés, el rollback aborta.
        Schema::table('content_media', function (Blueprint $table): void {
            $table->dropForeign(['topic_item_id']);
            $table->dropIndex(['topic_item_id', 'type']);
        });

        Schema::table('content_media', function (Blueprint $table): void {
            $table->dropColumn(['topic_item_id', 'legacy_file_id']);
        });

        // Y se devuelve la columna a como estaba: obligatoria.
        Schema::table('content_media', fn (Blueprint $table) => $table->dropForeign(['content_id']));
        Schema::table('content_media', fn (Blueprint $table) => $table->unsignedBigInteger('content_id')->nullable(false)->change());
        Schema::table('content_media', fn (Blueprint $table) => $table->foreign('content_id')
            ->references('id')->on('contents')->cascadeOnDelete());
    }
};
