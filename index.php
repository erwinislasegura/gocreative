<?php
$meta = [
    'title' => 'Diseño web y soluciones digitales en Chile | Go Creative',
    'description' => 'Creamos páginas web, tiendas online, software a medida, automatizaciones y campañas Meta Ads para empresas en Chile. Cotiza con Go Creative.',
    'path' => '/',
    'image' => '/assets/img/agency-web-design-v2.webp',
    'image_alt' => 'Equipo de Go Creative diseñando una experiencia web profesional para empresas en Chile',
    'image_width' => 1939,
    'image_height' => 811,
];
$active = 'inicio';
require __DIR__ . '/includes/header.php';
?>
<section class="hero hero--home" data-hero-motion>
    <div class="hero__ambient" aria-hidden="true"></div>
    <div class="hero__media" aria-hidden="true"><img src="/assets/img/agency-web-design-v2.webp" alt="" width="1939" height="811" fetchpriority="high"></div>
    <div class="hero__overlay"></div>
    <div class="container hero__grid">
        <div class="hero__content" data-reveal>
            <p class="eyebrow"><span></span> Agencia digital · Los Ángeles, Chile</p>
            <h1>Diseño web, software y marketing <em>sin soluciones genéricas.</em></h1>
            <p class="hero__lead">Unimos estrategia visual, desarrollo y crecimiento digital para que tu empresa se vea mejor, venda mejor y trabaje con más claridad.</p>
            <div class="hero__actions">
                <a class="button button--lime" href="/contacto/">Cuéntanos tu proyecto <span>↗</span></a>
                <a class="button button--ghost" href="/portafolio/">Ver proyectos</a>
            </div>
            <div class="hero__trust">
                <div><strong>1.500+</strong><span>proyectos y clientes</span></div>
                <div><strong>360°</strong><span>estrategia + ejecución</span></div>
                <div><strong>Chile</strong><span>atención nacional</span></div>
            </div>
        </div>
    </div>
    <div class="hero__interface" aria-hidden="true">
        <div class="hero-ui hero-ui--status">
            <i></i><span>Sistema creativo activo</span>
        </div>
        <div class="hero-ui hero-ui--project">
            <span class="hero-ui__label">Proyecto digital / 01</span>
            <strong>Ideas que se convierten<br>en experiencias útiles.</strong>
            <div class="hero-ui__progress"><i></i></div>
            <small><span>ESTRATEGIA</span><span>DISEÑO</span><span>DESARROLLO</span></small>
        </div>
        <div class="hero-ui hero-ui--metric">
            <span>Enfoque</span><strong>Diseño + tecnología</strong>
        </div>
    </div>
</section>

<section class="proof-strip">
    <div class="container proof-strip__inner">
        <p>Capacidades conectadas</p>
        <div><span>Web corporativa</span><span>Ecommerce</span><span>Software</span><span>Automatización</span><span>Meta Ads</span><span>Diseño creativo</span></div>
    </div>
</section>

<section class="section section--light services-showcase" id="servicios">
    <div class="container">
        <div class="section-heading section-heading--split" data-reveal>
            <div><p class="eyebrow eyebrow--dark"><span></span> Lo que hacemos</p><h2>Una agencia para pensar, construir y hacer crecer.</h2></div>
            <div class="services-showcase__intro">
                <p>No vendemos piezas aisladas. Conectamos marca, experiencia, tecnología y adquisición para resolver el problema completo.</p>
                <div><span>Estrategia</span><span>Experiencia</span><span>Resultados</span></div>
            </div>
        </div>
        <div class="service-grid">
            <a class="service-card service-card--featured service-card--visual" href="/diseno-web-chile/" data-reveal="up">
                <span class="service-card__media" aria-hidden="true"><img src="/assets/img/agency-web-design-v2.webp" alt="" width="1939" height="811" loading="lazy" decoding="async"></span>
                <span class="service-card__top"><span class="service-card__number">01 / WEB</span><span class="service-card__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="1"/><path d="M3 9h18M7 6.5h.01M10 6.5h.01"/></svg></span></span>
                <span class="service-card__body"><h3>Diseño y desarrollo web</h3><p>Sitios corporativos y landing pages con una experiencia visual que genera confianza y convierte.</p></span>
                <strong>Explorar servicio <span>↗</span></strong>
            </a>
            <a class="service-card service-card--commerce" href="/tiendas-online/" data-reveal="up">
                <span class="service-card__top"><span class="service-card__number">02 / COMMERCE</span><span class="service-card__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h2l2 10h9l2-7H7M9 19h.01M17 19h.01"/></svg></span></span>
                <span class="service-card__body"><h3>Tiendas online</h3><p>Compra rápida, pagos, envíos y una administración simple.</p></span><strong>Explorar <span>↗</span></strong>
            </a>
            <a class="service-card service-card--system" href="/software-a-medida/" data-reveal="up">
                <span class="service-card__top"><span class="service-card__number">03 / SYSTEM</span><span class="service-card__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 6-5 6 5 6M15 6l5 6-5 6M13 4l-2 16"/></svg></span></span>
                <span class="service-card__body"><h3>Software a medida</h3><p>Plataformas, usuarios, reportes y control adaptados a tu operación.</p></span><strong>Explorar <span>↗</span></strong>
            </a>
            <a class="service-card service-card--automation" href="/automatizacion/" data-reveal="up">
                <span class="service-card__top"><span class="service-card__number">04 / FLOW</span><span class="service-card__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="12" cy="18" r="2"/><path d="M8 6h8M7 8l4 8M17 8l-4 8"/></svg></span></span>
                <span class="service-card__body"><h3>Automatización</h3><p>Procesos conectados para reducir errores y recuperar horas.</p></span><strong>Explorar <span>↗</span></strong>
            </a>
            <a class="service-card service-card--visual service-card--growth" href="/meta-ads/" data-reveal="up">
                <span class="service-card__media" aria-hidden="true"><img src="/assets/img/agency-marketing-v2.webp" alt="" width="1983" height="793" loading="lazy" decoding="async"></span>
                <span class="service-card__top"><span class="service-card__number">05 / GROWTH</span><span class="service-card__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="M15 9l5-5M17 4h3v3"/></svg></span></span>
                <span class="service-card__body"><h3>Campañas Meta Ads</h3><p>Publicidad medible para transformar atención en oportunidades.</p></span><strong>Explorar <span>↗</span></strong>
            </a>
            <a class="service-card service-card--visual service-card--brand" href="/diseno-creativo-digital/" data-reveal="up">
                <span class="service-card__media" aria-hidden="true"><img src="/assets/img/agency-branding-v2.webp" alt="" width="1918" height="820" loading="lazy" decoding="async"></span>
                <span class="service-card__top"><span class="service-card__number">06 / BRAND</span><span class="service-card__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3Z"/><path d="m19 16 .8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8L19 16Z"/></svg></span></span>
                <span class="service-card__body"><h3>Diseño creativo digital</h3><p>Identidad y contenido visual para construir una marca reconocible.</p></span><strong>Explorar <span>↗</span></strong>
            </a>
        </div>
    </div>
</section>

<section class="parallax-band parallax-band--left parallax-band--home"
         aria-labelledby="home-parallax-title"
         data-parallax>
    <div class="parallax-band__media" data-parallax-media>
        <img src="/assets/img/parallax-strategy.webp"
             alt="Equipo de Go Creative planificando la estrategia, estructura y diseño de un proyecto digital"
             width="1920"
             height="768"
             loading="lazy"
             decoding="async">
    </div>
    <div class="parallax-band__overlay" aria-hidden="true"></div>
    <div class="container parallax-band__inner">
        <div class="parallax-band__card" data-reveal>
            <p class="eyebrow"><span></span> Diseño con propósito</p>
            <h2 id="home-parallax-title">Primero entendemos. Después diseñamos y desarrollamos.</h2>
            <p>La estética importa, pero funciona mejor cuando nace de una estrategia clara, mensajes concretos y una experiencia pensada para las personas.</p>
            <div class="parallax-band__meta" aria-label="Etapas de trabajo">
                <span>Diagnóstico</span><span>Experiencia</span><span>Implementación</span>
            </div>
            <a class="text-link" href="/nosotros/">Así trabajamos <span>→</span></a>
        </div>
    </div>
</section>

<section class="section section--dark process-showcase">
    <div class="container process-showcase__grid">
        <div data-reveal>
            <p class="eyebrow"><span></span> Método Go Creative</p>
            <h2>La tecnología debe adaptarse a tu empresa, no al revés.</h2>
            <p>Entendemos cómo trabajan las personas, cómo se mueven los datos y dónde se pierde tiempo. Después convertimos ese diagnóstico en una solución simple y escalable.</p>
            <a class="text-link" href="/nosotros/">Conoce cómo trabajamos <span>→</span></a>
        </div>
        <ol class="process-list" data-reveal>
            <li><span>01</span><div><h3>Diagnóstico</h3><p>Objetivo, usuarios, problema y prioridades.</p></div></li>
            <li><span>02</span><div><h3>Estrategia y diseño</h3><p>Arquitectura, contenido y experiencia visual.</p></div></li>
            <li><span>03</span><div><h3>Desarrollo</h3><p>Funciones, integraciones y configuración.</p></div></li>
            <li><span>04</span><div><h3>Pruebas y lanzamiento</h3><p>QA, optimización, publicación y capacitación.</p></div></li>
        </ol>
    </div>
</section>

<section class="section photo-editorial" aria-labelledby="visual-work-title">
    <div class="container">
        <div class="section-heading section-heading--split" data-reveal>
            <div>
                <p class="eyebrow eyebrow--dark"><span></span> Tecnología en contexto</p>
                <h2 id="visual-work-title">Soluciones digitales que se conectan con el trabajo real.</h2>
            </div>
            <p>Comercio, campañas, indicadores y procesos forman parte de una misma experiencia cuando la tecnología está bien integrada.</p>
        </div>
        <div class="photo-editorial__grid">
            <figure class="photo-editorial__item photo-editorial__item--wide" data-reveal>
                <img src="/assets/img/parallax-commerce.webp"
                     alt="Planificación de ecommerce, ventas y marketing digital con métricas en computador y teléfono"
                     width="1920"
                     height="781"
                     loading="lazy"
                     decoding="async">
                <figcaption><span>Ecommerce y marketing</span><strong>Vender, medir y mejorar</strong></figcaption>
            </figure>
            <figure class="photo-editorial__item" data-reveal>
                <img src="/assets/img/parallax-software.webp"
                     alt="Equipo revisando un sistema empresarial con tableros, reportes e indicadores"
                     width="1823"
                     height="863"
                     loading="lazy"
                     decoding="async">
                <figcaption><span>Software y automatización</span><strong>Control para decidir</strong></figcaption>
            </figure>
            <figure class="photo-editorial__item" data-reveal>
                <img src="/assets/img/agency-marketing-v2.webp"
                     alt="Equipo creativo preparando contenido y analizando el rendimiento de campañas digitales"
                     width="1983"
                     height="793"
                     loading="lazy"
                     decoding="async">
                <figcaption><span>Campañas digitales</span><strong>Contenido que atrae</strong></figcaption>
            </figure>
            <figure class="photo-editorial__item photo-editorial__item--wide" data-reveal>
                <img src="/assets/img/agency-branding-v2.webp"
                     alt="Proceso de diseño de una identidad visual con aplicaciones digitales y materiales de marca"
                     width="1918"
                     height="820"
                     loading="lazy"
                     decoding="async">
                <figcaption><span>Identidad y diseño</span><strong>Marcas que se reconocen</strong></figcaption>
            </figure>
        </div>
    </div>
</section>

<section class="section portfolio-preview">
    <div class="container">
        <div class="section-heading section-heading--split" data-reveal>
            <div><p class="eyebrow eyebrow--dark"><span></span> Trabajo reciente</p><h2>Marcas mejor presentadas. Experiencias que se sienten profesionales.</h2></div>
            <p>Cada proyecto combina una navegación simple, mensajes concretos y una identidad visual pensada para el mercado de cada cliente.</p>
        </div>
        <div class="portfolio-grid">
            <?php foreach (array_slice($portfolioItems, 0, 6) as $index => $item): ?>
            <article class="portfolio-card <?= $index === 0 ? 'portfolio-card--wide' : '' ?>" data-reveal>
                <div class="portfolio-card__image"><img src="/assets/img/portfolio/<?= e($item['image']) ?>" alt="Proyecto web <?= e($item['name']) ?>" width="960" height="515" loading="lazy" decoding="async"></div>
                <div class="portfolio-card__body"><div><span><?= e($item['type']) ?></span><h3><?= e($item['name']) ?></h3></div><span class="portfolio-card__arrow">↗</span></div>
            </article>
            <?php endforeach; ?>
        </div>
        <div class="section-action"><a class="button button--dark" href="/portafolio/">Ver portafolio completo <span>→</span></a></div>
    </div>
</section>

<section class="section section--mist plans-section">
    <div class="container">
        <div class="section-heading section-heading--center" data-reveal>
            <p class="eyebrow eyebrow--dark"><span></span> Planes de desarrollo web</p>
            <h2>Una base profesional para comenzar a captar más clientes.</h2>
            <p>Valores referenciales con pago único. El alcance final se confirma después de revisar tu proyecto.</p>
        </div>
        <div class="plan-grid">
            <article class="plan-card" data-reveal><span class="plan-card__tag">Para comenzar rápido</span><h3>One Page Express</h3><p>Una página compacta para presentar tu negocio y recibir consultas.</p><div class="price"><small>desde</small><strong>$55.000</strong><span>pago único</span></div><ul><li>Diseño responsive</li><li>Hasta 5 bloques de contenido</li><li>WhatsApp y formulario</li><li>SEO técnico inicial</li></ul><a class="button button--outline" href="<?= e(whatsapp_url('Hola, quiero consultar por la One Page Express.')) ?>" target="_blank" rel="noopener">Consultar disponibilidad</a></article>
            <article class="plan-card" data-reveal><span class="plan-card__tag">Para negocios en crecimiento</span><h3>Web Pyme</h3><p>Sitio corporativo multipágina para presentar tus servicios, generar confianza y recibir nuevas consultas.</p><div class="price"><small>desde</small><strong>$160.000</strong><span>pago único</span></div><ul><li>Diseño responsive adaptado a tu marca</li><li>Hasta 4 páginas internas</li><li>WhatsApp y formulario de contacto</li><li>SEO técnico inicial</li></ul><a class="button button--outline" href="<?= e(whatsapp_url('Hola, quiero cotizar una Web Pyme.')) ?>" target="_blank" rel="noopener">Cotizar Web Pyme</a></article>
            <article class="plan-card plan-card--featured" data-reveal><span class="plan-card__tag">Más solicitado</span><h3>Web Profesional</h3><p>Sitio informativo para empresas de servicios que quieren destacar.</p><div class="price"><small>desde</small><strong>$220.000</strong><span>pago único</span></div><ul><li>Diseño personalizado</li><li>Páginas internas</li><li>Autoadministrable</li><li>SEO base y capacitación</li></ul><a class="button button--lime" href="<?= e(whatsapp_url('Hola, quiero cotizar una Web Profesional.')) ?>" target="_blank" rel="noopener">Cotizar mi web</a></article>
            <article class="plan-card" data-reveal><span class="plan-card__tag plan-card__tag--sale">Oferta especial</span><h3>Tienda Online</h3><p>Ecommerce completo con catálogo, carrito, pagos y pedidos.</p><div class="price price--sale"><small>precio oferta <del>$560.000</del></small><strong>$260.000</strong><span>pago único</span></div><ul><li>Productos y categorías</li><li>Carrito y checkout</li><li>Pagos y métodos de envío</li><li>Panel y capacitación</li></ul><a class="button button--outline" href="<?= e(whatsapp_url('Hola, quiero cotizar la Tienda Online en oferta a $260.000.')) ?>" target="_blank" rel="noopener">Cotizar ecommerce</a></article>
        </div>
    </div>
</section>

<section class="section testimonials">
    <div class="container">
        <div class="section-heading section-heading--split" data-reveal>
            <div><p class="eyebrow eyebrow--dark"><span></span> Confianza que se construye</p><h2>Clientes que hoy se muestran mejor y trabajan con más claridad.</h2></div>
            <p>Escuchamos, ordenamos y acompañamos cada implementación hasta que la solución esté lista para usarse.</p>
        </div>
        <div class="testimonial-grid">
            <blockquote data-reveal><div class="stars">★★★★★</div><p>“Logramos una web profesional y fácil de usar para nuestra corredora. La entrega fue a tiempo y ahora recibimos más consultas.”</p><footer><strong>Cliente inmobiliario</strong><span>Puerto Montt · Puerto Varas</span></footer></blockquote>
            <blockquote data-reveal><div class="stars">★★★★★</div><p>“El equipo entendió nuestra visión y creó una web hermosa, clara y coherente con la calidad de nuestro trabajo floral.”</p><footer><strong>Emprendimiento floral</strong><span>Santiago de Chile</span></footer></blockquote>
            <blockquote data-reveal><div class="stars">★★★★★</div><p>“El sitio refleja la seriedad de nuestra empresa de construcción. La comunicación fue clara y el soporte siempre oportuno.”</p><footer><strong>Empresa constructora</strong><span>Chile</span></footer></blockquote>
        </div>
    </div>
</section>

<section class="section faq-section">
    <div class="container faq-layout">
        <div data-reveal><p class="eyebrow eyebrow--dark"><span></span> Preguntas frecuentes</p><h2>Claridad antes de comenzar.</h2><p>Definimos alcance, etapas, entregables y responsabilidades antes de iniciar cada proyecto.</p><a class="text-link text-link--dark" href="/contacto/">Hacer otra pregunta <span>→</span></a></div>
        <div class="faq-list" data-reveal>
            <article data-faq><button type="button" aria-expanded="false"><span>¿Qué incluye el desarrollo de una página web?</span><i>+</i></button><div><p>Incluye planificación, estructura, diseño responsive, desarrollo, carga de contenidos acordados, SEO base, formularios, pruebas y publicación según el plan elegido.</p></div></article>
            <article data-faq><button type="button" aria-expanded="false"><span>¿La página se verá bien en celulares?</span><i>+</i></button><div><p>Sí. Diseñamos cada vista para computador, tablet y teléfono, cuidando navegación, lectura, botones y velocidad.</p></div></article>
            <article data-faq><button type="button" aria-expanded="false"><span>¿Pueden automatizar tareas de mi empresa?</span><i>+</i></button><div><p>Sí. Automatizamos formularios, asignaciones, alertas, correos, documentos, seguimiento de estados y reportes después de revisar el flujo real.</p></div></article>
            <article data-faq><button type="button" aria-expanded="false"><span>¿Entregan soporte después de publicar?</span><i>+</i></button><div><p>Realizamos una revisión final, capacitación y soporte inicial. También podemos acordar mantenimiento continuo según las necesidades del proyecto.</p></div></article>
        </div>
    </div>
</section>

<section class="cta-band">
    <div class="container cta-band__inner" data-reveal>
        <div><p class="eyebrow"><span></span> Tu siguiente paso</p><h2>Convierte una idea en una ventaja digital.</h2><p>Cuéntanos qué necesitas mejorar y te orientaremos con una propuesta clara.</p></div>
        <div class="cta-band__actions"><a class="button button--lime" href="/contacto/">Evaluemos tu proyecto <span>↗</span></a><a href="tel:<?= e(SITE_PHONE_LINK) ?>"><?= e(SITE_PHONE_DISPLAY) ?></a></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
