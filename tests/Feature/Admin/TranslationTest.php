<?php

namespace Tests\Feature\Admin;

use App\Models\Content;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Traducción del contenido con la API de Google.
 *
 * El contenido llega del portal en español y no hay versión en inglés. Se
 * traduce una vez, se guarda y se sirve como cualquier otro texto: traducir al
 * pintar ataría cada visita a que un servicio de fuera responda.
 */
class TranslationTest extends TestCase
{
    use RefreshDatabase;

    private function noticia(array $overrides = []): Content
    {
        return Content::create(array_merge([
            'title' => 'Jornada de donación de sangre',
            'category' => Content::NEWS_CATEGORY,
            'body' => '<p>Ven a donar.</p>',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    private function fakeApi(array $traducciones): void
    {
        Http::fake([
            'translation.googleapis.com/*' => Http::response([
                'data' => ['translations' => array_map(fn ($t) => ['translatedText' => $t], $traducciones)],
            ]),
        ]);

        config(['services.google_translate.key' => 'clave-de-prueba']);
    }

    /* ------------------------------------------------------------------ */

    public function test_sin_traduccion_el_sitio_sirve_el_espanol(): void
    {
        $noticia = $this->noticia();

        app()->setLocale('en');

        // Preferible una ficha en español a una ficha con huecos.
        $this->assertSame('Jornada de donación de sangre', $noticia->translated('title'));
        $this->assertFalse($noticia->isTranslated('title'));
    }

    public function test_se_sirve_la_traduccion_cuando_existe(): void
    {
        $noticia = $this->noticia();

        $noticia->translations()->create([
            'locale' => 'en',
            'field' => 'title',
            'value' => 'Blood donation drive',
            'source_hash' => Translation::hashOf($noticia->title),
        ]);

        app()->setLocale('en');

        $this->assertSame('Blood donation drive', $noticia->fresh()->translated('title'));
        $this->assertTrue($noticia->fresh()->isTranslated('title'));

        // Y en español nunca se antepone: el original manda.
        app()->setLocale('es');
        $this->assertSame('Jornada de donación de sangre', $noticia->fresh()->translated('title'));
    }

    /**
     * Una traducción vieja no se sirve.
     *
     * La importación reescribe el contenido en cada pasada. Si el portal cambió
     * el texto y todavía no se ha vuelto a traducir, servir la traducción
     * anterior diría algo distinto de lo que dice el original, y nadie se
     * enteraría. Se sirve el español nuevo hasta que se retraduzca.
     */
    public function test_una_traduccion_de_un_texto_que_cambio_no_se_sirve(): void
    {
        $noticia = $this->noticia();

        $noticia->translations()->create([
            'locale' => 'en',
            'field' => 'title',
            'value' => 'Blood donation drive',
            'source_hash' => Translation::hashOf('Otro titular que ya no está'),
        ]);

        app()->setLocale('en');

        $this->assertSame('Jornada de donación de sangre', $noticia->fresh()->translated('title'));
    }

    public function test_el_comando_traduce_y_guarda_con_su_huella(): void
    {
        $noticia = $this->noticia();

        $this->fakeApi(['Blood donation drive', '<p>Come and donate.</p>']);

        $this->artisan('huv:traducir', ['--modelo' => 'contenidos'])->assertSuccessful();

        $titulo = Translation::where('field', 'title')->sole();

        $this->assertSame('Blood donation drive', $titulo->value);
        $this->assertSame(Translation::hashOf($noticia->title), $titulo->source_hash);

        // El original no se toca: es con el que se busca y con el que se importa.
        $this->assertSame('Jornada de donación de sangre', $noticia->fresh()->title);
    }

    /** Lo que ya está al día no se vuelve a mandar: cada carácter se paga. */
    public function test_no_se_retraduce_lo_que_no_ha_cambiado(): void
    {
        $this->noticia();
        $this->fakeApi(['Blood donation drive', '<p>Come and donate.</p>']);

        $this->artisan('huv:traducir', ['--modelo' => 'contenidos'])->assertSuccessful();

        Http::fake(); // Cualquier llamada nueva daría una respuesta vacía y fallaría.

        $this->artisan('huv:traducir', ['--modelo' => 'contenidos'])
            ->expectsOutputToContain('todo está al día')
            ->assertSuccessful();
    }

    /**
     * Lo que el portal cambió sí se vuelve a traducir.
     *
     * Es la otra mitad del ahorro: saltarse todo lo que ya tenga traducción
     * costaría lo mismo que hacerlo bien y dejaría el inglés congelado en la
     * versión del día en que se tradujo, diciendo algo distinto del español.
     */
    public function test_lo_que_cambio_en_el_portal_se_vuelve_a_traducir(): void
    {
        $noticia = $this->noticia();

        // Dos respuestas seguidas: la del primer paso y la del segundo. Con dos
        // llamadas a Http::fake() los sellos se acumulan y el primero gana, así
        // que la segunda tanda recibiría las traducciones de la primera.
        config(['services.google_translate.key' => 'clave-de-prueba']);

        Http::fakeSequence()
            ->push(['data' => ['translations' => [
                ['translatedText' => 'Blood donation drive'],
                ['translatedText' => '<p>Come and donate.</p>'],
            ]]])
            ->push(['data' => ['translations' => [
                ['translatedText' => 'Blood donation drive postponed'],
            ]]]);

        $this->artisan('huv:traducir', ['--modelo' => 'contenidos'])->assertSuccessful();

        // El portal reescribe el titular en la siguiente importación.
        $noticia->update(['title' => 'Jornada de donación aplazada']);

        $this->artisan('huv:traducir', ['--modelo' => 'contenidos'])
            ->doesntExpectOutputToContain('todo está al día')
            ->assertSuccessful();

        app()->setLocale('en');

        $this->assertSame('Blood donation drive postponed', $noticia->fresh()->translated('title'));
    }
    /** Sin clave no se llama a nadie, y se dice por qué. */
    public function test_sin_clave_el_comando_avisa_y_no_llama_a_la_api(): void
    {
        $this->noticia();

        Http::fake();
        config(['services.google_translate.key' => null]);

        $this->artisan('huv:traducir')
            ->expectsOutputToContain('GOOGLE_TRANSLATE_KEY')
            ->assertFailed();

        Http::assertNothingSent();
        $this->assertDatabaseCount('translations', 0);
    }

    /** La simulación cuenta y estima, pero no gasta. */
    public function test_la_simulacion_no_llama_a_la_api(): void
    {
        $this->noticia();

        Http::fake();

        $this->artisan('huv:traducir', ['--simular' => true])
            ->expectsOutputToContain('Coste estimado')
            ->assertSuccessful();

        Http::assertNothingSent();
        $this->assertDatabaseCount('translations', 0);
    }
}
