<?php
$meta = [
    'title' => 'Agencia de diseño web y soluciones digitales | Go Creative',
    'description' => 'Conoce Go Creative Chile: estrategia, diseño y tecnología para crear páginas web, sistemas y soluciones digitales de alto nivel.',
    'path' => '/nosotros/',
];
$active = 'nosotros';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="inner-hero about-hero">
    <div class="container">
        <div class="breadcrumbs"><a href="/">Inicio</a><span>/</span><span>Nosotros</span></div>
        <div class="inner-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Go Creative Chile</p>
                <h1>Ideas con identidad.<br><em>Tecnología con propósito.</em></h1>
                <p class="inner-hero__lead">Somos un estudio digital que mezcla diseño, desarrollo y marketing para crear soluciones que se ven distintas y funcionan en el mundo real.</p>
                <div class="about-hero__signals" aria-label="Especialidades de Go Creative">
                    <span>Diseño web</span><span>Software</span><span>Marketing</span>
                </div>
            </div>
            <div class="inner-hero__aside about-hero__proof">
                <strong>1.500+</strong>
                <p>clientes y proyectos que nos han enseñado a escuchar antes de diseñar.</p>
                <a class="text-link" href="/portafolio/">Explorar trabajos <span>→</span></a>
            </div>
        </div>
        <div class="about-hero__stamp" aria-hidden="true"><span>GO</span><small>Creative studio<br>Chile · 2026</small></div>
    </div>
</section>

<section class="about-ticker" aria-label="Capacidades de Go Creative">
    <div class="about-ticker__track">
        <span>Observar</span><i>✦</i><span>Imaginar</span><i>✦</i><span>Diseñar</span><i>✦</i><span>Construir</span><i>✦</i><span>Medir</span><i>✦</i><span>Mejorar</span>
    </div>
</section>

<section class="about-story">
    <div class="container about-story__grid">
        <figure class="about-story__media" data-reveal>
            <img src="/assets/img/agency-web-design-v2.webp"
                 alt="Diseñadores de Go Creative trabajando en prototipos y experiencias digitales"
                 width="1939"
                 height="811"
                 loading="lazy"
                 decoding="async">
            <figcaption><span>Estudio digital</span><strong>Los Ángeles · Chile</strong></figcaption>
        </figure>
        <div class="about-story__content" data-reveal>
            <p class="eyebrow eyebrow--dark"><span></span> Nuestro propósito</p>
            <h2>Hacer simple lo complejo y memorable lo cotidiano.</h2>
            <p>Convertimos información dispersa, ideas y necesidades operativas en experiencias digitales claras. Cada decisión visual debe aportar identidad; cada decisión técnica debe resolver algo concreto.</p>
            <p>Trabajamos desde Los Ángeles, Biobío, con empresas de todo Chile y una relación cercana durante cada etapa.</p>
            <div class="about-story__facts">
                <div><strong>Diseño</strong><span>para diferenciar</span></div>
                <div><strong>Tecnología</strong><span>para avanzar</span></div>
                <div><strong>Datos</strong><span>para decidir</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section about-method">
    <div class="container">
        <div class="section-heading section-heading--split" data-reveal>
            <div><p class="eyebrow eyebrow--dark"><span></span> Cómo trabajamos</p><h2>Un proceso visible, flexible y sin cajas negras.</h2></div>
            <p>Compartimos prioridades, avances y decisiones para que cada etapa tenga sentido y el proyecto mantenga su dirección.</p>
        </div>
        <div class="about-method__grid">
            <article class="about-method__card about-method__card--dark" data-reveal><span>01</span><div><h3>Escuchamos el contexto</h3><p>Objetivos, usuarios, restricciones y oportunidades antes de abrir cualquier herramienta.</p></div></article>
            <article class="about-method__card" data-reveal><span>02</span><div><h3>Ordenamos el desafío</h3><p>Definimos alcance, contenidos, funciones, etapas y entregables para avanzar con claridad.</p></div></article>
            <article class="about-method__card about-method__card--color" data-reveal><span>03</span><div><h3>Diseñamos y construimos</h3><p>Unimos identidad, experiencia y tecnología en una solución coherente y responsive.</p></div></article>
            <article class="about-method__card about-method__card--lime" data-reveal><span>04</span><div><h3>Probamos y acompañamos</h3><p>Validamos, ajustamos, publicamos y dejamos al equipo preparado para usar la solución.</p></div></article>
        </div>
    </div>
</section>

<section class="section about-values">
    <div class="container">
        <div class="section-heading section-heading--split" data-reveal>
            <div><p class="eyebrow eyebrow--dark"><span></span> Lo que nos mueve</p><h2>Creatividad útil, no decoración.</h2></div>
            <p>Buscamos una personalidad clara para cada proyecto sin sacrificar velocidad, comprensión ni facilidad de uso.</p>
        </div>
        <div class="about-values__grid">
            <article class="about-value about-value--wide" data-reveal><span>✦</span><div><h3>Diseño con criterio</h3><p>Una estética propia que nace de la estrategia, del mercado y de las personas que usarán la solución.</p></div></article>
            <article class="about-value" data-reveal><span>↔</span><div><h3>Trabajo cercano</h3><p>Comunicación directa, decisiones explicadas y avances visibles.</p></div></article>
            <article class="about-value about-value--accent" data-reveal><span>↗</span><div><h3>Base para crecer</h3><p>Sistemas y sitios preparados para sumar contenido, funciones e integraciones.</p></div></article>
        </div>
    </div>
</section>

<section class="cta-band">
    <div class="container cta-band__inner">
        <div><h2>¿Construimos algo que no se vea como todo lo demás?</h2><p>Cuéntanos qué necesita cambiar y diseñaremos un camino claro para conseguirlo.</p></div>
        <div class="cta-band__actions"><a class="button button--lime" href="/contacto/">Hablar con Go Creative <span>↗</span></a></div>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
