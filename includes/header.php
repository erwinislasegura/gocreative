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

$meta = array_merge([
    'title' => 'Go Creative | Desarrollo web y soluciones digitales',
    'description' => 'Diseño web, tiendas online, software a medida, automatización, Meta Ads y soporte técnico para empresas en Chile.',
    'path' => '/',
    'image' => '/assets/img/hero-team.webp',
], $meta ?? []);

$active = $active ?? '';
$currentCanonical = canonical($meta['path']);
$ogImage = str_starts_with($meta['image'], 'http') ? $meta['image'] : canonical($meta['image']);

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
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="author" content="Go Creative Chile">
    <meta name="theme-color" content="#07111f">
    <link rel="canonical" href="<?= e($currentCanonical) ?>">
    <link rel="icon" href="/assets/img/favicon.png" type="image/png">
    <link rel="preload" href="/assets/css/main.css?v=1.0.0" as="style">
    <link rel="stylesheet" href="/assets/css/main.css?v=1.0.0">
    <meta property="og:locale" content="es_CL">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Go Creative Chile">
    <meta property="og:title" content="<?= e($meta['title']) ?>">
    <meta property="og:description" content="<?= e($meta['description']) ?>">
    <meta property="og:url" content="<?= e($currentCanonical) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <script type="application/ld+json">
    <?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => ['Organization', 'ProfessionalService'],
        '@id' => SITE_URL . '/#organization',
        'name' => 'Go Creative Chile',
        'url' => SITE_URL,
        'logo' => canonical('/assets/img/logo.webp'),
        'image' => $ogImage,
        'email' => SITE_EMAIL,
        'telephone' => SITE_PHONE_LINK,
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Los Ángeles',
            'addressRegion' => 'Biobío',
            'addressCountry' => 'CL',
        ],
        'areaServed' => ['Chile'],
        'sameAs' => ['https://www.facebook.com/profile.php?id=61572961960110'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
</head>
<body>
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
