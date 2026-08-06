<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agenda institucional.
     *
     * Un evento puede pertenecer a varias categorías —2025, Educación,
     * Participación Social en Salud— y el bloque de la portada decide cuáles
     * muestra.
     */
    public function up(): void
    {
        Schema::create('event_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 80)->unique();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 150);
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->nullable();
            $table->string('place', 200)->nullable();
            $table->string('url', 2048)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('event_event_category', function (Blueprint $table): void {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['event_id', 'event_category_id']);
        });

        // Opciones libres del bloque: la sección de origen y las categorías
        // elegidas. Se guardan aquí para no crear una tabla por cada ajuste.
        Schema::table('content_blocks', function (Blueprint $table): void {
            $table->json('options')->nullable()->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('content_blocks', function (Blueprint $table): void {
            $table->dropColumn('options');
        });

        Schema::dropIfExists('event_event_category');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_categories');
    }
};
