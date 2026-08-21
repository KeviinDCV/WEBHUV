@php
    use App\Support\ConfigLabel;
    use App\Support\StructuredData;

    $institution = config('huv.institution');
    $seo = config('huv.seo');
    $services = config('huv.services');

    /** Datos estructurados schema.org para buscadores (rich results). */
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => ['Hospital', 'GovernmentOrganization'],
        // Un identificador estable para poder referir la organización desde el
        // bloque de cada ficha, en lugar de repetirla entera en cada página.
        '@id' => StructuredData::organizationId(),
        'name' => $institution['name'],
        'alternateName' => 'HUV',
        'url' => url('/'),
        'logo' => asset('img/logo-huv.png'),
        'image' => asset('img/og-huv.png'),
        'description' => ConfigLabel::of($seo, 'description', 'descripcion'),
        'taxID' => $institution['nit'],
        'foundingDate' => (string) $institution['founded_year'],
        'email' => $institution['email'],
        'telephone' => $institution['switchboard_tel'],
        'medicalSpecialty' => collect($services['items'])
            ->map(fn ($especialidad, $posicion) => ConfigLabel::item($services, 'items', $posicion, $especialidad))
            ->all(),
        'parentOrganization' => [
            '@type' => 'GovernmentOrganization',
            'name' => $institution['oversight'],
        ],
        // Los perfiles oficiales: es lo que usa un buscador para saber que esas
        // cuentas son de la institución y no de un tercero que la suplanta.
        'sameAs' => collect(config('huv.footer.social'))->pluck('url')->filter()->values()->all(),
        // El horario de atención administrativa, el mismo que publica el pie.
        // Urgencias no entra aquí: son veinticuatro horas y declararlo en el
        // mismo bloque diría que todo el hospital abre a las siete.
        'openingHoursSpecification' => collect([['07:00', '12:00'], ['13:00', '17:30']])
            ->map(fn (array $tramo): array => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'opens' => $tramo[0],
                'closes' => $tramo[1],
            ])
            ->all(),
        'areaServed' => [
            '@type' => 'AdministrativeArea',
            'name' => $institution['state'],
            'containedInPlace' => ['@type' => 'Country', 'name' => $institution['country']],
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $institution['address'],
            'addressLocality' => $institution['city'],
            'addressRegion' => $institution['state'],
            'postalCode' => $institution['postal_code'],
            'addressCountry' => 'CO',
        ],
        'contactPoint' => [
            [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'name' => __('menu.lineas.atencion.rotulo'),
                'telephone' => $institution['user_service_tel'],
                'email' => $institution['email'],
                'availableLanguage' => ['es'],
            ],
            [
                '@type' => 'ContactPoint',
                'contactType' => 'emergency',
                'name' => __('menu.lineas.urgencias.rotulo'),
                'telephone' => $institution['switchboard_tel'],
            ],
        ],
    ];
@endphp

<script type="application/ld+json">
    {!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>
