@php
    $institution = config('huv.institution');

    /** Datos estructurados schema.org para buscadores (rich results). */
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => ['Hospital', 'GovernmentOrganization'],
        'name' => $institution['name'],
        'alternateName' => 'HUV',
        'url' => url('/'),
        'logo' => asset('img/logo-huv.png'),
        'image' => asset('img/og-huv.png'),
        'description' => config('huv.seo.description'),
        'taxID' => $institution['nit'],
        'foundingDate' => (string) $institution['founded_year'],
        'email' => $institution['email'],
        'telephone' => $institution['switchboard_tel'],
        'medicalSpecialty' => config('huv.services.items'),
        'parentOrganization' => [
            '@type' => 'GovernmentOrganization',
            'name' => $institution['oversight'],
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
                'name' => 'Atención al usuario',
                'telephone' => $institution['user_service_tel'],
                'email' => $institution['email'],
                'availableLanguage' => ['es'],
            ],
            [
                '@type' => 'ContactPoint',
                'contactType' => 'emergency',
                'name' => 'Urgencias',
                'telephone' => $institution['switchboard_tel'],
            ],
        ],
    ];
@endphp

<script type="application/ld+json">
    {!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>
