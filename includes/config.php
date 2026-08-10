<?php
declare(strict_types=1);

const SITE_NAME = 'Go Creative';
const SITE_URL = 'https://gocreative.cl';
const SITE_EMAIL = 'contacto@gocreative.cl';
const SITE_PHONE_DISPLAY = '+56 9 5215 7840';
const SITE_PHONE_LINK = '+56952157840';
const SITE_WHATSAPP = '56952157840';
const SITE_CITY = 'Los Ángeles, Biobío, Chile';


/**
 * Returns the URL path where the project is mounted.
 *
 * In production the project root matches DOCUMENT_ROOT and this returns an
 * empty string. In XAMPP installations such as htdocs/gocreative it returns
 * /gocreative, keeping every local asset and internal link reachable.
 */
function site_base_path(): string
{
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $projectRoot = realpath(dirname(__DIR__));

    if ($documentRoot === false || $projectRoot === false) {
        return $basePath = '';
    }

    $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
    $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');

    if (strcasecmp($projectRoot, $documentRoot) === 0) {
        return $basePath = '';
    }

    $documentPrefix = $documentRoot . '/';
    if (str_starts_with(strtolower($projectRoot), strtolower($documentPrefix))) {
        return $basePath = '/' . trim(substr($projectRoot, strlen($documentRoot)), '/');
    }

    return $basePath = '';
}

function site_path(string $path = '/'): string
{
    $basePath = site_base_path();

    if ($basePath === '') {
        return '/' . ltrim($path, '/');
    }

    if ($path === $basePath || str_starts_with($path, $basePath . '/')) {
        return $path;
    }

    return $basePath . '/' . ltrim($path, '/');
}

/**
 * Prefixes root-relative URLs rendered by the site when it runs in a
 * subdirectory. External URLs, canonical metadata and protocol-relative URLs
 * are left untouched.
 */
function rewrite_site_urls(string $html): string
{
    if (site_base_path() === '') {
        return $html;
    }

    $rewritten = preg_replace_callback(
        '~\b(href|src|action|poster)=([\'"])(/(?!/)[^\'"]*)\2~i',
        static function (array $matches): string {
            return $matches[1] . '=' . $matches[2] . site_path($matches[3]) . $matches[2];
        },
        $html
    );

    return $rewritten ?? $html;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function canonical(string $path = '/'): string
{
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

function whatsapp_url(string $message = 'Hola Go Creative, quiero cotizar un proyecto digital.'): string
{
    return 'https://wa.me/' . SITE_WHATSAPP . '?text=' . rawurlencode($message);
}

$portfolioItems = [
    ['image' => '1.webp', 'name' => 'AdLinks', 'type' => 'Marketing digital', 'description' => 'Sitio de agencia con servicios de marketing, producción audiovisual y posicionamiento SEO.'],
    ['image' => '2.webp', 'name' => 'Club Ossandón', 'type' => 'Web deportiva', 'description' => 'Plataforma corporativa para entrenamiento, tenis, gimnasio, boxeo y vida saludable.'],
    ['image' => '3.webp', 'name' => 'Contreras & Stevens', 'type' => 'Sitio corporativo', 'description' => 'Presencia digital para una firma legal con servicios, equipo y contacto directo.'],
    ['image' => '4.webp', 'name' => 'MetrikaLab', 'type' => 'Consultoría estratégica', 'description' => 'Web para una consultora que transforma datos y tendencias en decisiones accionables.'],
    ['image' => '5.webp', 'name' => 'Palermo Vestidos', 'type' => 'Catálogo visual', 'description' => 'Experiencia elegante para vestidos de novia, fiesta y gala con foco en asesoría.'],
    ['image' => '6.webp', 'name' => 'Panal Construcciones', 'type' => 'Web de servicios', 'description' => 'Proyecto corporativo para construcción, remodelaciones, obras civiles y estructuras.'],
    ['image' => '7.webp', 'name' => 'Solución Emprendedor', 'type' => 'Servicios profesionales', 'description' => 'Sitio enfocado en servicios contables y tributarios para emprendedores.'],
    ['image' => '8.webp', 'name' => 'SURMA', 'type' => 'Ecommerce', 'description' => 'Tienda online de electrodomésticos con catálogo y experiencia comercial ordenada.'],
];
