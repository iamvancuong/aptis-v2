@props([
    'title' => null,        // Tiêu đề riêng của trang (chưa ghép hậu tố)
    'description' => null,
    'image' => null,
    'keywords' => null,
    'canonical' => null,
    'type' => 'website',    // og:type — 'website' | 'article'
    'noindex' => false,     // true → chặn index (trang riêng tư)
])

@php
    $siteName   = config('seo.site_name');
    $suffix     = config('seo.title_suffix');
    // Ghép hậu tố "· Milaedu", nhưng không nhân đôi nếu tiêu đề đã chứa tên site.
    $fullTitle  = $title
        ? (\Illuminate\Support\Str::contains($title, $siteName) ? $title : $title . ' · ' . $suffix)
        : config('seo.default_title');
    $desc       = $description ?: config('seo.default_description');
    $kw         = $keywords ?: config('seo.keywords');
    // Canonical dựng từ domain thật (APP_URL) + path, KHÔNG dùng host của request —
    // tránh bản trùng http/https/www và bỏ query (vd ?goi=week gộp về /register).
    if (! $canonical) {
        $path = request()->path();
        $canonical = rtrim(config('app.url'), '/') . ($path === '/' ? '' : '/' . $path);
    }
    // Ảnh OG tuyệt đối (Google/Facebook yêu cầu URL đầy đủ).
    $ogImage    = $image ?: config('seo.og_image');
    $ogImageAbs = \Illuminate\Support\Str::startsWith($ogImage, ['http://', 'https://'])
        ? $ogImage
        : rtrim(config('app.url'), '/') . '/' . ltrim($ogImage, '/');
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $desc }}">
<meta name="keywords" content="{{ $kw }}">
<link rel="canonical" href="{{ $canonical }}">
@if(config('seo.google_site_verification'))
    {{-- Xác minh sở hữu tên miền cho Google Search Console (cách "HTML tag"). --}}
    <meta name="google-site-verification" content="{{ config('seo.google_site_verification') }}">
@endif
@if($noindex)
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow, max-image-preview:large">
@endif

{{-- Open Graph (Facebook, Zalo, messenger…) --}}
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImageAbs }}">
<meta property="og:locale" content="{{ config('seo.locale') }}">

{{-- Twitter/X --}}
<meta name="twitter:card" content="{{ config('seo.twitter_card') }}">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $desc }}">
<meta name="twitter:image" content="{{ $ogImageAbs }}">
