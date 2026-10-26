<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="theme-color" content="#B3262E" />

<title>
    {{ filled($title ?? null) ? $title . ' — ' . config('app.name', 'DonorLink UG') : config('app.name', 'DonorLink UG') }}
</title>

<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/favicon.svg">

@vite(['resources/css/app.css', 'resources/js/app.js'])
