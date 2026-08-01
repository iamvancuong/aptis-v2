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

    // @graph: liên kết Website ↔ Tổ chức giáo dục ↔ Giảng viên (Person). Tên giảng
    // viên là NỘI DUNG THẬT trong structured data — cách hợp lệ để Google hiểu web
    // này gắn với "cô Dung" + "Aptis", không phải giấu chữ cho bot.
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
                : $url . '/' . ltrim($instructor['photo'], '/')] : []),
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
