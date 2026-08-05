@props(['name'])

<svg {{ $attributes->class('size-7') }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('calendar-check')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M8 3v4M16 3v4M3 10h18M9 15.5l2 2 4-4" />
            @break

        @case('graduation')
            <path d="m12 4 9 4.5-9 4.5-9-4.5L12 4Z" />
            <path d="M6.5 11v4.6c0 .5.3.9.7 1.1 1.3.7 3 1.3 4.8 1.3s3.5-.6 4.8-1.3c.4-.2.7-.6.7-1.1V11" />
            <path d="M21 8.5v5" />
            @break

        @case('map-pin')
            <path d="M20 10.5c0 5.2-8 12-8 12s-8-6.8-8-12a8 8 0 1 1 16 0Z" />
            <circle cx="12" cy="10.5" r="2.8" />
            @break

        @case('lab')
            <path d="M9 3h6M10.5 3v6.2L4.8 18a2 2 0 0 0 1.7 3h11a2 2 0 0 0 1.7-3l-5.7-8.8V3" />
            <path d="M7.6 14.5h8.8" />
            @break

        @case('payment')
            <rect x="2.5" y="5" width="19" height="14" rx="2" />
            <path d="M2.5 9.8h19" />
            <path d="M6.5 14.8h3.5" />
            @break

        @case('inbox')
            <path d="M3.5 12.5h4l1.5 3h6l1.5-3h4" />
            <path d="M5.4 5.3a2 2 0 0 1 1.8-1.1h9.6a2 2 0 0 1 1.8 1.1l2.4 6.3v6.4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6.4Z" />
            @break

        @case('chart')
            <path d="M3.5 20.5h17" />
            <path d="m5.5 15.5 4.2-4.4 3.2 3 5.6-6" />
            <path d="M18.5 5v3.6h-3.6" />
            @break

        @case('info')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v5.5" />
            <path d="M12 7.6h.01" />
            @break

        @case('gavel')
            <path d="M3.5 20.5h9" />
            <path d="M12 3.5 8 7.5M16.5 8 12.5 12" />
            <rect x="9.2" y="4.6" width="7.6" height="3.6" rx="1" transform="rotate(45 13 6.4)" />
            <path d="m10.2 9.8-4.4 4.4a1.7 1.7 0 0 0 0 2.4l.8.8a1.7 1.7 0 0 0 2.4 0l4.4-4.4" />
            @break

        @case('megaphone')
            <path d="M3.5 10v4a1.5 1.5 0 0 0 1.5 1.5h2L14 20V4L7 8.5H5A1.5 1.5 0 0 0 3.5 10Z" />
            <path d="M17.5 9.2a4 4 0 0 1 0 5.6" />
            <path d="M20 6.6a7.6 7.6 0 0 1 0 10.8" />
            <path d="M7 15.5V20h2.5" />
            @break
    @endswitch
</svg>
