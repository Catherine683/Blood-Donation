@props([
    'title',
    'description' => null,
])

<div class="flex w-full flex-col text-center mb-1">
    <h1 class="text-2xl font-bold tracking-tight text-neutral-900 font-sans">
        {{ $title }}
    </h1>
    @if ($description)
        <p class="mt-2 text-sm text-neutral-600 font-sans leading-relaxed">
            {{ $description }}
        </p>
    @endif
</div>
