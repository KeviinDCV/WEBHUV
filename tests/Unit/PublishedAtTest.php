<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * La fecha que encabeza cada ficha.
 *
 * Siempre en relativo, con la misma escala que el portal. Dos cosas que Carbon
 * no hace por su cuenta y hay que reproducir: redondear en todas las unidades
 * —2,95 años son «hace 3 años» allí y «hace 2» en Carbon— y saltar de unidad
 * donde salta el portal, no en el número redondo.
 */
class PublishedAtTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function render(string $fecha): string
    {
        return trim(strip_tags(
            (string) view('components.published-at', ['value' => Carbon::parse($fecha)])->render()
        ));
    }

    /**
     * Casos medidos en el portal, cada uno con su fecha real.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function medidos(): array
    {
        return [
            // 7,41 días. Notificaciones Judiciales, 12 de agosto de 2026.
            'siete días' => ['2026-08-12 07:56:59', '2026-08-04 22:14:15', 'Hace 7 días'],
            // 5,65 días: truncando saldría «5». Noticias, mismo día.
            'seis días redondeando' => ['2026-08-12 07:56:59', '2026-08-06 16:12:08', 'Hace 6 días'],
            // 2,95 años: truncando saldría «2». Tablas de retención documental.
            'tres años redondeando' => ['2026-08-12 12:00:00', '2023-08-30 01:21:00', 'Hace 3 años'],
            // 3,22 años. Datos abiertos.
            'tres años' => ['2026-08-12 12:00:00', '2023-05-24 22:16:00', 'Hace 3 años'],
            // Restructuración.
            'veintiún días' => ['2026-08-12 12:00:00', '2026-07-22 09:45:00', 'Hace 21 días'],
            'dos meses' => ['2026-08-12 12:00:00', '2026-06-11 08:35:00', 'Hace 2 meses'],
            'tres meses' => ['2026-08-12 12:00:00', '2026-05-07 09:37:00', 'Hace 3 meses'],
            // Rendición de cuentas.
            'cinco meses' => ['2026-08-12 12:00:00', '2026-03-05 16:37:00', 'Hace 5 meses'],
            // 137,24 días. Dividiendo por el mes medio salen 4,5085 y el
            // redondeo los sube a cinco; contando por el calendario son cuatro
            // meses justos más quince días, o sea 4,49. Encuestas de
            // satisfacción, 14 de agosto de 2026.
            'cuatro meses y medio' => ['2026-08-14 15:22:26', '2026-03-30 09:43:17', 'Hace 4 meses'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('medidos')]
    public function test_reproduce_lo_que_escribe_el_portal(string $hoy, string $fecha, string $esperado): void
    {
        Carbon::setTestNow($hoy);

        $this->assertSame($esperado, $this->render($fecha));
    }

    /**
     * Al sumar meses no se desborda el mes corto.
     *
     * Al 30 de agosto le siguen el 28 de febrero y el 30 de marzo, no el 2 de
     * marzo. Es lo que hace la biblioteca del portal, y no es un detalle: un
     * contenido del 30 de agosto de 2023 visto el 1 de marzo de 2026 son 2,503
     * años sin desbordar y 2,497 desbordando, así que el redondeo cae a un lado
     * o al otro y el rótulo cambia en un año entero. Barriendo cinco años de
     * publicaciones vistas desde cuatro fechas distintas salen 54 días en que
     * pasa justo esto.
     */
    public function test_al_sumar_meses_no_se_desborda_el_mes_corto(): void
    {
        Carbon::setTestNow('2026-03-01 09:00:00');

        $this->assertSame('Hace 3 años', $this->render('2023-08-30 09:00:00'));
    }

    /**
     * Los saltos de unidad van donde los pone el portal, no en el número
     * redondo: con saltos redondos, veinte días saldrían como «un mes».
     */
    public function test_los_saltos_de_unidad_no_estan_en_el_numero_redondo(): void
    {
        Carbon::setTestNow('2026-08-12 12:00:00');

        $this->assertSame('Hace 21 horas', $this->render('2026-08-11 15:00:00'));
        $this->assertSame('Hace un día', $this->render('2026-08-11 11:00:00'));
        $this->assertSame('Hace 25 días', $this->render('2026-07-18 12:00:00'));
        $this->assertSame('Hace un mes', $this->render('2026-07-10 12:00:00'));
        $this->assertSame('Hace 10 meses', $this->render('2025-10-15 12:00:00'));
        $this->assertSame('Hace un año', $this->render('2025-06-15 12:00:00'));
    }

    public function test_lo_de_hace_un_rato(): void
    {
        Carbon::setTestNow('2026-08-12 12:00:00');

        $this->assertSame('Hace unos segundos', $this->render('2026-08-12 11:59:40'));
        $this->assertSame('Hace un minuto', $this->render('2026-08-12 11:58:40'));
        $this->assertSame('Hace 30 minutos', $this->render('2026-08-12 11:30:00'));
        $this->assertSame('Hace 3 horas', $this->render('2026-08-12 09:00:00'));
    }

    /** Nada pasa nunca a fecha absoluta, ni un documento de hace años. */
    public function test_nada_pasa_nunca_a_fecha_absoluta(): void
    {
        Carbon::setTestNow('2026-08-12 12:00:00');

        foreach (['2026-08-05 09:00:00', '2026-06-10 09:00:00', '2020-01-01 09:00:00'] as $fecha) {
            $this->assertStringStartsWith('Hace ', $this->render($fecha), "«{$fecha}» salió con fecha absoluta.");
        }
    }

    /** Y Carbon diría «1 semana», que el portal no usa nunca. */
    public function test_nunca_dice_semanas(): void
    {
        Carbon::setTestNow('2026-08-12 12:00:00');

        $this->assertStringNotContainsString('semana', $this->render('2026-08-06 09:00:00'));
    }

    /**
     * La fecha exacta no se pierde de vista, solo del texto: sigue en el
     * atributo que leen buscadores y lectores de pantalla.
     */
    public function test_la_fecha_exacta_sigue_en_el_marcado(): void
    {
        Carbon::setTestNow('2026-08-12 12:00:00');

        $html = (string) view('components.published-at', ['value' => Carbon::parse('2023-05-24 22:16:00')])->render();

        $this->assertStringContainsString('datetime="2023-05-24T22:16:00', $html);
        $this->assertStringContainsString('title="', $html);
    }
}
