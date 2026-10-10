@php
    $url        = rtrim(config('app.url'), '/');
    $siteName   = config('seo.site_name');
    $desc       = config('seo.default_description');
    $instructor = config('seo.instructor');
    $email      = config('seo.contact.email');
    $ogImage    = config('seo.og_image');
    $logoAbs    = \Illuminate\Support\Str::startsWith($ogImage, ['http://', 'https://'])
        ? $ogImage
        : $url . '/' . ltrim($ogImage, '/');

    // @graph: liên kết Website ↔ Tổ chức giáo dục (↔ Giảng viên/Person khi bật
    // SEO_SHOW_INSTRUCTOR — tắt thì không đưa tên giảng viên vào JSON-LD).
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
        $instructor['show'] ? [
            '@type'       => 'Person',
            '@id'         => $url . '/#instructor',
            'name'        => $instructor['name'],
            'jobTitle'    => $instructor['job_title'],
            'description' => $instructor['bio'],
            'worksFor'    => ['@id' => $url . '/#org'],
            'knowsAbout'  => ['Aptis', 'Aptis Speaking', 'Aptis Writing'],
            // Chỉ khai `image` khi có ảnh THẬT — khai ảnh placeholder là dữ liệu sai.
        ] + (!empty($instructor['photo']) ? ['image' => \Illuminate\Support\Str::startsWith($instructor['photo'], ['http://', 'https://'])
                ? $instructor['photo']
                : $url . '/' . ltrim($instructor['photo'], '/')] : []) : null,
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
        '@graph'   => array_values(array_filter($graph)),
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
