{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('drives.index') }}</loc>
        <changefreq>hourly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ route('privacy') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    @foreach ($publishedDrives as $drive)
        <url>
            <loc>{{ route('drives.show', $drive) }}</loc>
            <lastmod>{{ $drive->updated_at?->toAtomString() }}</lastmod>
            <changefreq>hourly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach
</urlset>
