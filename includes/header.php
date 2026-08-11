<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');


if (!defined('SITE_URL_REWRITE_BUFFER_STARTED')) {
    define('SITE_URL_REWRITE_BUFFER_STARTED', true);
    ob_start('rewrite_site_urls');
}

$pageMeta = $meta ?? [];
$meta = array_merge([
    'title' => 'Go Creative | Desarrollo web y soluciones digitales',
    'description' => 'Diseño web, tiendas online, software a medida, automatización, Meta Ads y soporte técnico para empresas en Chile.',
    'path' => '/',
    'image' => '/assets/img/hero-team.webp',
    'image_alt' => 'Equipo de Go Creative desarrollando soluciones digitales para empresas en Chile',
    'image_width' => 1800,
    'image_height' => 1202,
    'index' => http_response_code() < 400,
], $pageMeta);

$visualScene = $visualScenes[$meta['path']] ?? null;
if ($visualScene !== null && !array_key_exists('image', $pageMeta)) {
    $meta['image'] = $visualScene['hero_image'] ?? $visualScene['image'];
    $meta['image_alt'] = $visualScene['hero_alt'] ?? $visualScene['alt'];
    $meta['image_width'] = $visualScene['hero_width'] ?? $visualScene['width'];
    $meta['image_height'] = $visualScene['hero_height'] ?? $visualScene['height'];
}

$active = $active ?? '';
$currentCanonical = canonical($meta['path']);
$ogImage = str_starts_with($meta['image'], 'http') ? $meta['image'] : canonical($meta['image']);
$robotsDirective = ($meta['index'] ?? true)
    ? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'
    : 'noindex, follow';
$breadcrumbs = $seoBreadcrumbs[$meta['path']] ?? [];
$serviceSeo = $seoServices[$meta['path']] ?? null;

$organizationId = rtrim(SITE_URL, '/') . '/#organization';
$websiteId = rtrim(SITE_URL, '/') . '/#website';
$pageId = $currentCanonical . '#webpage';
$imageId = $currentCanonical . '#primaryimage';
$schemaGraph = [];

if ($meta['path'] === '/') {
    $schemaGraph[] = [
        '@type' => ['Organization', 'ProfessionalService'],
        '@id' => $organizationId,
        'name' => 'Go Creative Chile',
        'alternateName' => SITE_NAME,
        'url' => rtrim(SITE_URL, '/') . '/',
        'logo' => [
            '@type' => 'ImageObject',
            '@id' => rtrim(SITE_URL, '/') . '/#logo',
            'url' => canonical('/assets/img/logo.webp'),
            'contentUrl' => canonical('/assets/img/logo.webp'),
            'width' => 260,
            'height' => 147,
            'caption' => 'Go Creative Chile',
        ],
        'image' => ['@id' => $imageId],
        'email' => SITE_EMAIL,
        'telephone' => SITE_PHONE_LINK,
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Los Ángeles',
            'addressRegion' => 'Biobío',
            'addressCountry' => 'CL',
        ],
        'areaServed' => [
            '@type' => 'Country',
            'name' => 'Chile',
        ],
        'contactPoint' => [
            [
                '@type' => 'ContactPoint',
                'telephone' => SITE_PHONE_LINK,
                'contactType' => 'sales',
                'areaServed' => 'CL',
                'availableLanguage' => ['Spanish'],
            ],
        ],
        'sameAs' => ['https://www.facebook.com/profile.php?id=61572961960110'],
    ];
}

$schemaGraph[] = [
    '@type' => 'WebSite',
    '@id' => $websiteId,
    'url' => rtrim(SITE_URL, '/') . '/',
    'name' => 'Go Creative Chile',
    'description' => 'Diseño web, ecommerce, software a medida, automatización y marketing digital para empresas en Chile.',
    'publisher' => ['@id' => $organizationId],
    'inLanguage' => 'es-CL',
];

$pageSchema = [
    '@type' => 'WebPage',
    '@id' => $pageId,
    'url' => $currentCanonical,
    'name' => $meta['title'],
    'description' => $meta['description'],
    'isPartOf' => ['@id' => $websiteId],
    'about' => ['@id' => $organizationId],
    'primaryImageOfPage' => ['@id' => $imageId],
    'inLanguage' => 'es-CL',
];

if (count($breadcrumbs) > 1) {
    $breadcrumbId = $currentCanonical . '#breadcrumb';
    $pageSchema['breadcrumb'] = ['@id' => $breadcrumbId];
    $breadcrumbItems = [];

    foreach ($breadcrumbs as $position => $item) {
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => $position + 1,
            'name' => $item['name'],
            'item' => canonical($item['path']),
        ];
    }

    $schemaGraph[] = [
        '@type' => 'BreadcrumbList',
        '@id' => $breadcrumbId,
        'itemListElement' => $breadcrumbItems,
    ];
}

$schemaGraph[] = $pageSchema;
$schemaGraph[] = [
    '@type' => 'ImageObject',
    '@id' => $imageId,
    'url' => $ogImage,
    'contentUrl' => $ogImage,
    'caption' => $meta['image_alt'],
    'width' => $meta['image_width'],
    'height' => $meta['image_height'],
    'representativeOfPage' => true,
];

if ($serviceSeo !== null) {
    $schemaGraph[] = [
        '@type' => 'Service',
        '@id' => $currentCanonical . '#service',
        'name' => $serviceSeo['name'],
        'serviceType' => $serviceSeo['serviceType'],
        'description' => $serviceSeo['description'],
        'url' => $currentCanonical,
        'provider' => ['@id' => $organizationId],
        'areaServed' => [
            '@type' => 'Country',
            'name' => 'Chile',
        ],
        'audience' => [
            '@type' => 'BusinessAudience',
            'audienceType' => 'Empresas y emprendimientos',
        ],
    ];
}

$navItems = [
    'inicio' => ['Inicio', '/'],
    'servicios' => ['Servicios', '/servicios/'],
    'web' => ['Desarrollo web', '/diseno-web-chile/'],
    'portafolio' => ['Portafolio', '/portafolio/'],
    'nosotros' => ['Nosotros', '/nosotros/'],
    'contacto' => ['Contacto', '/contacto/'],
];
?>
<!doctype html>
<html lang="es-CL">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($meta['title']) ?></title>
    <meta name="description" content="<?= e($meta['description']) ?>">
    <meta name="robots" content="<?= e($robotsDirective) ?>">
    <meta name="googlebot" content="<?= e($robotsDirective) ?>">
    <meta name="author" content="Go Creative Chile">
    <meta name="theme-color" content="#07111f">
    <meta name="format-detection" content="telephone=yes">
    <link rel="canonical" href="<?= e($currentCanonical) ?>">
    <link rel="alternate" hreflang="es-CL" href="<?= e($currentCanonical) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= e($currentCanonical) ?>">
    <link rel="icon" href="/assets/img/favicon.png" type="image/png">
    <link rel="preload" href="/assets/css/main.css?v=1.5.0" as="style">
    <?php if ($meta['path'] === '/'): ?>
    <link rel="preload" href="/assets/img/agency-web-design-v2.webp" as="image" type="image/webp" fetchpriority="high">
    <?php elseif ($visualScene !== null): ?>
    <link rel="preload" href="<?= e($visualScene['hero_image'] ?? $visualScene['image']) ?>" as="image" type="image/webp" fetchpriority="high">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/main.css?v=1.5.0">
    <meta property="og:locale" content="es_CL">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Go Creative Chile">
    <meta property="og:title" content="<?= e($meta['title']) ?>">
    <meta property="og:description" content="<?= e($meta['description']) ?>">
    <meta property="og:url" content="<?= e($currentCanonical) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:secure_url" content="<?= e($ogImage) ?>">
    <meta property="og:image:type" content="image/webp">
    <meta property="og:image:width" content="<?= e((string) $meta['image_width']) ?>">
    <meta property="og:image:height" content="<?= e((string) $meta['image_height']) ?>">
    <meta property="og:image:alt" content="<?= e($meta['image_alt']) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($meta['title']) ?>">
    <meta name="twitter:description" content="<?= e($meta['description']) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">
    <meta name="twitter:image:alt" content="<?= e($meta['image_alt']) ?>">
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@graph' => $schemaGraph,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
</head>
<body<?php if ($visualScene !== null): ?> class="page-hero--<?= e($visualScene['hero_copy'] ?? 'left') ?>" style="--page-hero-image: url('<?= e(site_path($visualScene['hero_image'] ?? $visualScene['image'])) ?>'); --page-hero-position: <?= e($visualScene['hero_position'] ?? 'center') ?>"<?php endif; ?>>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<div class="topbar">
    <div class="container topbar__inner">
        <span>Soluciones digitales para empresas en todo Chile</span>
        <div class="topbar__links">
            <a href="tel:<?= e(SITE_PHONE_LINK) ?>"><?= e(SITE_PHONE_DISPLAY) ?></a>
            <a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a>
        </div>
    </div>
</div>
<header class="site-header" data-header>
    <div class="container header__inner">
        <a class="brand" href="/" aria-label="Go Creative, inicio">
            <img src="/assets/img/logo-white.webp" width="260" height="147" alt="Go Creative Chile" fetchpriority="high">
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-navigation" data-nav-toggle>
            <span></span><span></span><span></span><span class="sr-only">Abrir menú</span>
        </button>
        <nav class="main-nav" id="main-navigation" aria-label="Navegación principal" data-nav>
            <?php foreach ($navItems as $key => [$label, $href]): ?>
                <a href="<?= e($href) ?>" class="<?= $active === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <a class="button button--small button--lime nav-cta" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener">Cotizar proyecto</a>
        </nav>
    </div>
</header>
<main id="contenido">
