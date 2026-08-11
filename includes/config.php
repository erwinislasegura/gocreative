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

$seoBreadcrumbs = [
    '/servicios/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Servicios', 'path' => '/servicios/'],
    ],
    '/diseno-web-chile/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Servicios', 'path' => '/servicios/'],
        ['name' => 'Diseño web', 'path' => '/diseno-web-chile/'],
    ],
    '/tiendas-online/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Servicios', 'path' => '/servicios/'],
        ['name' => 'Tiendas online', 'path' => '/tiendas-online/'],
    ],
    '/software-a-medida/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Servicios', 'path' => '/servicios/'],
        ['name' => 'Software a medida', 'path' => '/software-a-medida/'],
    ],
    '/automatizacion/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Servicios', 'path' => '/servicios/'],
        ['name' => 'Automatización', 'path' => '/automatizacion/'],
    ],
    '/meta-ads/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Servicios', 'path' => '/servicios/'],
        ['name' => 'Meta Ads', 'path' => '/meta-ads/'],
    ],
    '/diseno-creativo-digital/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Servicios', 'path' => '/servicios/'],
        ['name' => 'Diseño creativo digital', 'path' => '/diseno-creativo-digital/'],
    ],
    '/soporte-tecnico/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Servicios', 'path' => '/servicios/'],
        ['name' => 'Soporte técnico', 'path' => '/soporte-tecnico/'],
    ],
    '/portafolio/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Portafolio', 'path' => '/portafolio/'],
    ],
    '/nosotros/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Nosotros', 'path' => '/nosotros/'],
    ],
    '/contacto/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Contacto', 'path' => '/contacto/'],
    ],
    '/politica-de-privacidad/' => [
        ['name' => 'Inicio', 'path' => '/'],
        ['name' => 'Política de privacidad', 'path' => '/politica-de-privacidad/'],
    ],
];

$seoServices = [
    '/diseno-web-chile/' => [
        'name' => 'Diseño y desarrollo web en Chile',
        'serviceType' => 'Diseño y desarrollo de sitios web',
        'description' => 'Sitios corporativos, landing pages y páginas autoadministrables con diseño responsive, SEO técnico y orientación comercial.',
    ],
    '/tiendas-online/' => [
        'name' => 'Diseño de tiendas online en Chile',
        'serviceType' => 'Desarrollo de ecommerce',
        'description' => 'Tiendas online con catálogo, carrito, pagos, envíos, inventario, pedidos y capacitación.',
    ],
    '/software-a-medida/' => [
        'name' => 'Desarrollo de software a medida en Chile',
        'serviceType' => 'Desarrollo de sistemas web a medida',
        'description' => 'Plataformas para operaciones, inventario, ventas, reservas, documentos, usuarios, permisos, reportes e indicadores.',
    ],
    '/automatizacion/' => [
        'name' => 'Automatización de procesos empresariales',
        'serviceType' => 'Automatización de procesos digitales',
        'description' => 'Automatización de formularios, alertas, asignaciones, documentos, correos e integraciones para empresas.',
    ],
    '/meta-ads/' => [
        'name' => 'Gestión de campañas Meta Ads',
        'serviceType' => 'Publicidad en Facebook e Instagram',
        'description' => 'Campañas Meta Ads orientadas a consultas, WhatsApp, oportunidades comerciales y medición de resultados.',
    ],
    '/diseno-creativo-digital/' => [
        'name' => 'Diseño creativo digital para empresas',
        'serviceType' => 'Diseño gráfico y contenido digital',
        'description' => 'Identidad visual, piezas para redes sociales, banners y recursos gráficos coherentes con cada marca.',
    ],
    '/soporte-tecnico/' => [
        'name' => 'Soporte técnico y mantenimiento web',
        'serviceType' => 'Soporte, mantenimiento y migración web',
        'description' => 'Diagnóstico, recuperación, optimización, seguridad y migración de sitios y sistemas web.',
    ],
];

$visualScenes = [
    '/servicios/' => [
        'image' => '/assets/img/parallax-strategy.webp',
        'width' => 1920,
        'height' => 768,
        'alt' => 'Equipo creativo de Go Creative planificando una estrategia digital y una experiencia web',
        'eyebrow' => 'Estrategia antes que tecnología',
        'title' => 'Diseño, contenido y desarrollo trabajando como una sola solución.',
        'description' => 'Cada proyecto comienza entendiendo el negocio, las personas y el resultado que necesitamos conseguir.',
        'align' => 'left',
    ],
    '/diseno-web-chile/' => [
        'image' => '/assets/img/parallax-strategy.webp',
        'width' => 1920,
        'height' => 768,
        'alt' => 'Profesionales de diseño web revisando arquitectura, contenido y prototipos digitales',
        'eyebrow' => 'Diseño con intención',
        'title' => 'Una web profesional se construye desde la estrategia.',
        'description' => 'Ordenamos mensajes, recorridos y llamados a la acción antes de convertirlos en una experiencia visual.',
        'align' => 'left',
    ],
    '/tiendas-online/' => [
        'image' => '/assets/img/parallax-commerce.webp',
        'width' => 1920,
        'height' => 781,
        'alt' => 'Equipo analizando ventas, productos y métricas de una tienda online',
        'eyebrow' => 'Comercio conectado',
        'title' => 'Catálogo, campañas y operación mirando el mismo resultado.',
        'description' => 'Diseñamos la compra para el cliente y la administración para el equipo que gestiona el negocio.',
        'align' => 'right',
    ],
    '/software-a-medida/' => [
        'image' => '/assets/img/parallax-software.webp',
        'width' => 1823,
        'height' => 863,
        'alt' => 'Equipo de desarrollo revisando tableros e indicadores de un sistema empresarial',
        'eyebrow' => 'Tecnología aplicada',
        'title' => 'El software debe reflejar cómo funciona realmente tu empresa.',
        'description' => 'Convertimos procesos, reglas y datos en una plataforma clara para usuarios, supervisores y gerencia.',
        'align' => 'right',
    ],
    '/automatizacion/' => [
        'image' => '/assets/img/parallax-software.webp',
        'width' => 1823,
        'height' => 863,
        'alt' => 'Especialistas configurando automatizaciones e indicadores para procesos empresariales',
        'eyebrow' => 'Procesos que avanzan',
        'title' => 'Menos tareas repetidas. Más tiempo para decidir y crecer.',
        'description' => 'Conectamos información, responsables y alertas para que el trabajo fluya con menos intervención manual.',
        'align' => 'right',
    ],
    '/meta-ads/' => [
        'image' => '/assets/img/parallax-commerce.webp',
        'width' => 1920,
        'height' => 781,
        'alt' => 'Equipo de marketing revisando métricas de campañas digitales y comercio electrónico',
        'eyebrow' => 'Decisiones con datos',
        'title' => 'Cada campaña necesita una meta comercial y una lectura clara.',
        'description' => 'Medimos conversaciones, oportunidades y acciones útiles para mejorar la inversión publicitaria.',
        'align' => 'right',
    ],
    '/diseno-creativo-digital/' => [
        'image' => '/assets/img/parallax-strategy.webp',
        'width' => 1920,
        'height' => 768,
        'alt' => 'Equipo creativo trabajando en identidad visual y piezas de comunicación digital',
        'eyebrow' => 'Identidad coherente',
        'title' => 'Una marca se reconoce cuando todas sus piezas hablan el mismo idioma.',
        'description' => 'Creamos sistemas visuales claros para que cada publicación, campaña y página fortalezca tu identidad.',
        'align' => 'left',
    ],
    '/soporte-tecnico/' => [
        'image' => '/assets/img/parallax-software.webp',
        'width' => 1823,
        'height' => 863,
        'alt' => 'Especialistas monitoreando sistemas y resolviendo incidencias técnicas',
        'eyebrow' => 'Continuidad digital',
        'title' => 'Diagnóstico, respaldo y decisiones técnicas con criterio.',
        'description' => 'Revisamos primero, protegemos la información y ejecutamos cambios controlados para reducir riesgos.',
        'align' => 'right',
    ],
    '/portafolio/' => [
        'image' => '/assets/img/parallax-strategy.webp',
        'width' => 1920,
        'height' => 768,
        'alt' => 'Equipo de Go Creative desarrollando experiencias digitales para diferentes empresas',
        'eyebrow' => 'Detrás de cada proyecto',
        'title' => 'Diseño profesional, decisiones concretas y trabajo colaborativo.',
        'description' => 'Cada resultado visual nace de entender una marca, su mercado y las personas que usarán la solución.',
        'align' => 'left',
    ],
    '/nosotros/' => [
        'image' => '/assets/img/parallax-strategy.webp',
        'width' => 1920,
        'height' => 768,
        'alt' => 'Equipo de Go Creative colaborando en un estudio de diseño y tecnología',
        'eyebrow' => 'Trabajo cercano',
        'title' => 'Escuchar bien es parte fundamental de diseñar una buena solución.',
        'description' => 'Acompañamos cada etapa con comunicación directa, prioridades visibles y decisiones explicadas.',
        'align' => 'left',
    ],
    '/contacto/' => [
        'image' => '/assets/img/parallax-strategy.webp',
        'width' => 1920,
        'height' => 768,
        'alt' => 'Profesionales de Go Creative conversando y planificando un nuevo proyecto digital',
        'eyebrow' => 'Comencemos con claridad',
        'title' => 'Una buena conversación puede ordenar el siguiente paso de tu empresa.',
        'description' => 'Cuéntanos el problema, el objetivo y lo que ya tienes. Te ayudaremos a definir un alcance realista.',
        'align' => 'left',
    ],
];
