@props([
    'title' => null,
    'maxWidth' => null,
])
@php
    $resolvedWidth = $maxWidth ?? $attributes->get('max-width') ?? $attributes->get('maxWidth') ?? 'max-w-md';
@endphp

<x-layouts::auth.card :title="$title" :maxWidth="$resolvedWidth" :max-width="$resolvedWidth" {{ $attributes }}>
    {{ $slot }}
</x-layouts::auth.card>
