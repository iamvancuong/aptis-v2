@php
    $url        = rtrim(config('app.url'), '/');
    $siteName   = config('seo.site_name');
    $desc       = config('seo.default_description');
    $email      = config('seo.contact.email');
    $ogImage    = config('seo.og_image');
    $logoAbs    = \Illuminate\Support\Str::startsWith($ogImage, ['http://', 'https://'])
        ? $ogImage
        : $url . '/' . ltrim($ogImage, '/');

    // @graph: liên kết Website ↔ Tổ chức giáo dục. Từ 10/2026 bỏ node Person của
    // giảng viên — không đưa tên giảng viên ra trang công khai.
    $graph = [
        [
            '@type'       => 'EducationalOrganization',
            '@id'         => $url . '/#org',
            'name'        => $siteName,
            'url'         => $url,
            'description' => $desc,
            'logo'        => $logoAbs,
            'email'       => $email,
            'knowsAbout'  => ['Aptis', 'Luyện thi Aptis', 'Aptis Speaking', 'Aptis Writing', 'Tiếng Anh'],
        ],
        [
            '@type'           => 'WebSite',
            '@id'             => $url . '/#website',
            'name'            => $siteName,
            'url'             => $url,
            'inLanguage'      => 'vi',
            'publisher'       => ['@id' => $url . '/#org'],
        ],
    ];

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
