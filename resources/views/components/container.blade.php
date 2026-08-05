@props(['as' => 'div'])

<{{ $as }} {{ $attributes->class('huv-container') }}>
    {{ $slot }}
</{{ $as }}>
