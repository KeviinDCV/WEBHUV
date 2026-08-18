<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se retira la agenda propia: la de verdad es un tema.
 *
 * En el portal la agenda ES el tema «Calendario de actividades», con sus ciento
 * cuarenta y un eventos, y cada uno se edita con el mismo formulario que
 * cualquier otro contenido. Aquí había además una tabla aparte con su propio
 * formulario y sus propias categorías, así que convivían dos agendas que no se
 * veían entre sí: un evento creado en una no salía en la otra.
 *
 * Se retira la de aquí. `down()` devuelve las tablas vacías: los eventos que
 * hubiera en ellas no se pueden recuperar, y por eso este paso solo se dio con
 * la agenda del portal ya importada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('event_event_category');
        Schema::dropIfExists('event_categories');
        Schema::dropIfExists('events');
    }

    public function down(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 150);
            $table->string('host', 70)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('place', 200)->nullable();
            $table->string('url', 2048)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('event_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 60)->unique();
            $table->timestamps();
        });

        Schema::create('event_event_category', function (Blueprint $table): void {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['event_id', 'event_category_id']);
        });
    }
};
