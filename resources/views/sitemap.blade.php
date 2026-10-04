{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($pages as $route => $updated)
    <url>
        <loc>{{ route($route) }}</loc>
@if ($updated)
        <lastmod>{{ \Illuminate\Support\Carbon::parse($updated)->toAtomString() }}</lastmod>
@endif
    </url>
@endforeach
</urlset>
