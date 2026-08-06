<?php

namespace Database\Seeders;

use App\Models\Content;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Traslada a la base de datos el contenido que hasta ahora vivía en
 * config/huv.php, para no arrancar con la portada vacía.
 *
 * Es idempotente: se identifica cada contenido por su slug, así que volver a
 * ejecutarlo no duplica nada ni pisa lo que se haya editado después.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->contents() as $attributes) {
            $slug = (new Content)->uniqueSlug($attributes['title']);

            Content::firstOrCreate(['slug' => $slug], $attributes + ['slug' => $slug]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function contents(): array
    {
        return [
            [
                'category' => 'Noticias',
                'title' => 'Suspensión temporal de servicios ambulatorios para afiliados de las EPS '
                    .'Emssanar, Asmet Salud y Coosalud por incumplimiento en los pagos',
                'excerpt' => 'Santiago de Cali, 5 de agosto de 2026. El Hospital Universitario del Valle '
                    .'«Evaristo García» E.S.E. informa a la comunidad, a los usuarios, a las autoridades '
                    .'de salud y a la opinión pública que…',
                'published_at' => Carbon::parse('-6 hours'),
                'is_featured' => true,
            ],
            [
                'category' => 'Noticias',
                'title' => 'El Hospital Universitario del Valle responde a Emssanar EPS: la verdad '
                    .'financiera y la defensa de la salud pública no son columnas',
                'excerpt' => 'Santiago de Cali, 4 de agosto de 2026. Ante el comunicado emitido por '
                    .'Emssanar EPS S.A.S., en respuesta a las declaraciones públicas de nuestra dirección…',
                'published_at' => Carbon::parse('-7 hours'),
            ],
            [
                'category' => 'Notificaciones Judiciales',
                'title' => 'Respuesta del caso 1208262026',
                'excerpt' => 'Nos permitimos informar que la respuesta a la solicitud radicada se '
                    .'encuentra disponible en la Oficina de Atención al Usuario.',
                'published_at' => Carbon::parse('-17 hours'),
            ],
            [
                'category' => 'Notificaciones Judiciales',
                'title' => 'Respuesta del caso 1210632026 - Vía Buzón de Sugerencias',
                'excerpt' => 'Nos permitimos informar que la respuesta a la comunicación recibida en el '
                    .'buzón de sugerencias se encuentra disponible.',
                'published_at' => Carbon::parse('-17 hours'),
            ],
            [
                'category' => 'Notificaciones Judiciales',
                'title' => 'Respuesta del caso 1210382026 - Vía Página Web',
                'excerpt' => 'En atención a la comunicación radicada por la página web, nos permitimos '
                    .'informar que la respuesta se encuentra disponible en la Oficina de Atención al Usuario.',
                'published_at' => Carbon::parse('-18 hours'),
            ],
            [
                'category' => 'Notificaciones Judiciales',
                'title' => 'Comunicado Cancelación en el Registro Público de carrera Administrativa ante la CNSC',
                'excerpt' => 'La Oficina Coordinadora de Talento Humano del HUV, en aplicación del '
                    .'principio de publicidad establecido en el numeral 5 del artículo 3 de la Ley 1437 de 2011…',
                'published_at' => Carbon::parse('-1 day'),
            ],
            [
                'category' => 'Noticias',
                'title' => 'HUV presentó resultados históricos durante su Rendición de Cuentas 2025',
                'excerpt' => 'La institución creció en servicios y fortaleció su operación durante la '
                    .'vigencia, según el informe presentado a la ciudadanía.',
                'published_at' => Carbon::parse('-7 days'),
            ],
            [
                'category' => 'Noticias',
                'title' => 'Garante del HUV denuncia presunta corrupción en pagos de EPS intervenidas y '
                    .'anuncia suspensión temporal de servicios',
                'excerpt' => 'El garante designado expone ante la ciudadanía la situación de los pagos de '
                    .'las entidades responsables intervenidas.',
                'published_at' => Carbon::parse('2026-07-28 13:00'),
            ],
            [
                'category' => 'Noticias',
                'title' => 'El HUV suspende temporalmente la atención a afiliados de Emssanar EPS por '
                    .'incumplimiento en los pagos',
                'excerpt' => 'La institución informa las medidas adoptadas ante el incumplimiento '
                    .'reiterado en los pagos de la entidad responsable.',
                'published_at' => Carbon::parse('2026-07-28 10:38'),
            ],
        ];
    }
}
