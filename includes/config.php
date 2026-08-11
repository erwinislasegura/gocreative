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
        'hero_image' => '/assets/img/agency-team-v2.webp',
        'hero_width' => 1815,
        'hero_height' => 867,
        'hero_position' => 'center 46%',
        'hero_alt' => 'Equipo multidisciplinario de Go Creative colaborando en una estrategia digital para una empresa',
        'hero_copy' => 'right',
        'image' => '/assets/img/parallax-strategy.webp',
        'width' => 1920,
        'height' => 768,
        'alt' => 'Equipo creativo de Go Creative planificando una estrategia digital y una experiencia web',
        'eyebrow' => 'Estrategia antes que tecnología',
        'title' => 'Diseño, contenido y desarrollo trabajando como una sola solución.',
        'description' => 'Cada proyecto comienza entendiendo el negocio, las personas y el resultado que necesitamos conseguir.',
        'align' => 'left',
        'tags' => ['Estrategia', 'Diseño', 'Tecnología'],
    ],
    '/diseno-web-chile/' => [
        'hero_image' => '/assets/img/agency-web-design-v2.webp',
        'hero_width' => 1939,
        'hero_height' => 811,
        'hero_position' => 'center 48%',
        'hero_alt' => 'Diseñadores web de Go Creative revisando prototipos responsive en un estudio digital',
        'image' => '/assets/img/agency-team-v2.webp',
        'width' => 1815,
        'height' => 867,
        'alt' => 'Profesionales de diseño web revisando arquitectura, contenido y prototipos digitales',
        'eyebrow' => 'Diseño con intención',
        'title' => 'Una web profesional se construye desde la estrategia.',
        'description' => 'Ordenamos mensajes, recorridos y llamados a la acción antes de convertirlos en una experiencia visual.',
        'align' => 'right',
        'tags' => ['Experiencia', 'Responsive', 'SEO'],
    ],
    '/tiendas-online/' => [
        'hero_image' => '/assets/img/parallax-commerce.webp',
        'hero_width' => 1920,
        'hero_height' => 781,
        'hero_position' => 'center 48%',
        'hero_alt' => 'Planificación de ecommerce con productos, ventas y métricas comerciales en dispositivos digitales',
        'image' => '/assets/img/agency-branding-v2.webp',
        'width' => 1918,
        'height' => 820,
        'alt' => 'Proceso de identidad visual y presentación de productos para una tienda online profesional',
        'eyebrow' => 'Comercio conectado',
        'title' => 'Catálogo, campañas y operación mirando el mismo resultado.',
        'description' => 'Diseñamos la compra para el cliente y la administración para el equipo que gestiona el negocio.',
        'align' => 'right',
        'tags' => ['Catálogo', 'Pagos', 'Conversión'],
    ],
    '/software-a-medida/' => [
        'hero_image' => '/assets/img/parallax-software.webp',
        'hero_width' => 1823,
        'hero_height' => 863,
        'hero_position' => 'center 48%',
        'hero_alt' => 'Equipo de desarrollo revisando tableros e indicadores de un software empresarial a medida',
        'hero_copy' => 'right',
        'image' => '/assets/img/agency-automation-v2.webp',
        'width' => 1823,
        'height' => 863,
        'alt' => 'Especialistas conectando flujos operativos, dispositivos y datos en un sistema empresarial',
        'eyebrow' => 'Tecnología aplicada',
        'title' => 'El software debe reflejar cómo funciona realmente tu empresa.',
        'description' => 'Convertimos procesos, reglas y datos en una plataforma clara para usuarios, supervisores y gerencia.',
        'align' => 'right',
        'tags' => ['Procesos', 'Datos', 'Control'],
    ],
    '/automatizacion/' => [
        'hero_image' => '/assets/img/agency-automation-v2.webp',
        'hero_width' => 1823,
        'hero_height' => 863,
        'hero_position' => 'center 48%',
        'hero_alt' => 'Especialistas diseñando un flujo de automatización para procesos operativos de una empresa',
        'hero_copy' => 'right',
        'image' => '/assets/img/parallax-software.webp',
        'width' => 1823,
        'height' => 863,
        'alt' => 'Equipo revisando tableros, reportes y reglas para automatizar procesos empresariales',
        'eyebrow' => 'Procesos que avanzan',
        'title' => 'Menos tareas repetidas. Más tiempo para decidir y crecer.',
        'description' => 'Conectamos información, responsables y alertas para que el trabajo fluya con menos intervención manual.',
        'align' => 'right',
        'tags' => ['Integraciones', 'Alertas', 'Eficiencia'],
    ],
    '/meta-ads/' => [
        'hero_image' => '/assets/img/agency-marketing-v2.webp',
        'hero_width' => 1983,
        'hero_height' => 793,
        'hero_position' => 'center 45%',
        'hero_alt' => 'Equipo de marketing preparando contenido y analizando resultados de una campaña digital',
        'image' => '/assets/img/parallax-commerce.webp',
        'width' => 1920,
        'height' => 781,
        'alt' => 'Equipo de marketing revisando métricas de campañas digitales y comercio electrónico',
        'eyebrow' => 'Decisiones con datos',
        'title' => 'Cada campaña necesita una meta comercial y una lectura clara.',
        'description' => 'Medimos conversaciones, oportunidades y acciones útiles para mejorar la inversión publicitaria.',
        'align' => 'right',
        'tags' => ['Campañas', 'Audiencias', 'Medición'],
    ],
    '/diseno-creativo-digital/' => [
        'hero_image' => '/assets/img/agency-branding-v2.webp',
        'hero_width' => 1918,
        'hero_height' => 820,
        'hero_position' => 'center 48%',
        'hero_alt' => 'Proceso profesional de diseño de identidad visual con piezas digitales y aplicaciones de marca',
        'hero_copy' => 'right',
        'image' => '/assets/img/agency-marketing-v2.webp',
        'width' => 1983,
        'height' => 793,
        'alt' => 'Equipo creativo produciendo contenido y evaluando métricas para una campaña de marca',
        'eyebrow' => 'Identidad coherente',
        'title' => 'Una marca se reconoce cuando todas sus piezas hablan el mismo idioma.',
        'description' => 'Creamos sistemas visuales claros para que cada publicación, campaña y página fortalezca tu identidad.',
        'align' => 'left',
        'tags' => ['Identidad', 'Contenido', 'Coherencia'],
    ],
    '/soporte-tecnico/' => [
        'hero_image' => '/assets/img/agency-support-v2.webp',
        'hero_width' => 1832,
        'hero_height' => 858,
        'hero_position' => 'center 50%',
        'hero_alt' => 'Especialistas de soporte revisando servidores, redes y el estado de una plataforma web',
        'hero_copy' => 'right',
        'image' => '/assets/img/agency-automation-v2.webp',
        'width' => 1823,
        'height' => 863,
        'alt' => 'Especialistas revisando flujos, dispositivos y diagnósticos para mantener una operación digital activa',
        'eyebrow' => 'Continuidad digital',
        'title' => 'Diagnóstico, respaldo y decisiones técnicas con criterio.',
        'description' => 'Revisamos primero, protegemos la información y ejecutamos cambios controlados para reducir riesgos.',
        'align' => 'right',
        'tags' => ['Seguridad', 'Rendimiento', 'Continuidad'],
    ],
    '/portafolio/' => [
        'hero_image' => '/assets/img/parallax-strategy.webp',
        'hero_width' => 1920,
        'hero_height' => 768,
        'hero_position' => 'center 44%',
        'hero_alt' => 'Equipo creativo planificando la estrategia y experiencia de distintos proyectos digitales',
        'image' => '/assets/img/agency-team-v2.webp',
        'width' => 1815,
        'height' => 867,
        'alt' => 'Equipo de Go Creative desarrollando experiencias digitales para diferentes empresas',
        'eyebrow' => 'Detrás de cada proyecto',
        'title' => 'Diseño profesional, decisiones concretas y trabajo colaborativo.',
        'description' => 'Cada resultado visual nace de entender una marca, su mercado y las personas que usarán la solución.',
        'align' => 'right',
        'tags' => ['Estrategia', 'Experiencia', 'Resultado'],
    ],
    '/nosotros/' => [
        'hero_image' => '/assets/img/agency-team-v2.webp',
        'hero_width' => 1815,
        'hero_height' => 867,
        'hero_position' => 'center 46%',
        'hero_alt' => 'Equipo de Go Creative colaborando con un cliente en un estudio de diseño y tecnología',
        'hero_copy' => 'right',
        'image' => '/assets/img/agency-branding-v2.webp',
        'width' => 1918,
        'height' => 820,
        'alt' => 'Detalle del proceso creativo y de las decisiones visuales que forman una identidad profesional',
        'eyebrow' => 'Trabajo cercano',
        'title' => 'Escuchar bien es parte fundamental de diseñar una buena solución.',
        'description' => 'Acompañamos cada etapa con comunicación directa, prioridades visibles y decisiones explicadas.',
        'align' => 'right',
        'tags' => ['Escucha', 'Colaboración', 'Criterio'],
    ],
    '/contacto/' => [
        'hero_image' => '/assets/img/agency-web-design-v2.webp',
        'hero_width' => 1939,
        'hero_height' => 811,
        'hero_position' => 'center 48%',
        'hero_alt' => 'Equipo de diseño preparando la propuesta visual de un nuevo proyecto web',
        'image' => '/assets/img/agency-team-v2.webp',
        'width' => 1815,
        'height' => 867,
        'alt' => 'Profesionales de Go Creative conversando y planificando un nuevo proyecto digital',
        'eyebrow' => 'Comencemos con claridad',
        'title' => 'Una buena conversación puede ordenar el siguiente paso de tu empresa.',
        'description' => 'Cuéntanos el problema, el objetivo y lo que ya tienes. Te ayudaremos a definir un alcance realista.',
        'align' => 'right',
        'tags' => ['Objetivo', 'Alcance', 'Propuesta'],
    ],
];
