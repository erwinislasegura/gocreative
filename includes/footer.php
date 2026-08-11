<?php
$visualScene = $visualScenes[$meta['path']] ?? null;
$showProjectStrip = !in_array(
    $meta['path'],
    ['/', '/portafolio/', '/politica-de-privacidad/', '/404'],
    true
);
?>
<?php if ($visualScene !== null): ?>
<section class="parallax-band parallax-band--<?= e($visualScene['align']) ?>"
         aria-labelledby="visual-break-title"
         data-parallax>
    <div class="parallax-band__media" data-parallax-media>
        <img src="<?= e($visualScene['image']) ?>"
             alt="<?= e($visualScene['alt']) ?>"
             width="<?= e((string) $visualScene['width']) ?>"
             height="<?= e((string) $visualScene['height']) ?>"
             loading="lazy"
             decoding="async">
    </div>
    <div class="parallax-band__overlay" aria-hidden="true"></div>
    <div class="container parallax-band__inner">
        <div class="parallax-band__card" data-reveal>
            <p class="eyebrow"><span></span> <?= e($visualScene['eyebrow']) ?></p>
            <h2 id="visual-break-title"><?= e($visualScene['title']) ?></h2>
            <p><?= e($visualScene['description']) ?></p>
            <div class="parallax-band__meta" aria-label="Áreas de trabajo">
                <?php foreach (($visualScene['tags'] ?? ['Estrategia', 'Diseño', 'Desarrollo']) as $tag): ?>
                <span><?= e($tag) ?></span>
                <?php endforeach; ?>
            </div>
            <a class="text-link" href="/nosotros/">Conoce nuestro método <span>→</span></a>
        </div>
    </div>
</section>
<?php endif; ?>
<?php if ($showProjectStrip): ?>
<section class="project-strip" aria-labelledby="project-strip-title">
    <div class="container">
        <div class="project-strip__head" data-reveal>
            <div>
                <p class="eyebrow eyebrow--dark"><span></span> Experiencia comprobable</p>
                <h2 id="project-strip-title">Proyectos digitales creados para empresas reales.</h2>
            </div>
            <a class="text-link text-link--dark" href="/portafolio/">Explorar portafolio <span>→</span></a>
        </div>
        <div class="project-strip__grid">
            <?php foreach (array_slice($portfolioItems, 0, 3) as $item): ?>
            <a class="project-strip__item" href="/portafolio/" aria-label="Ver proyecto <?= e($item['name']) ?> en el portafolio" data-reveal>
                <figure>
                    <img src="/assets/img/portfolio/<?= e($item['image']) ?>"
                         alt="Proyecto de <?= e($item['type']) ?> para <?= e($item['name']) ?> desarrollado por Go Creative"
                         width="960"
                         height="515"
                         loading="lazy"
                         decoding="async">
                    <figcaption><span><?= e($item['type']) ?></span><strong><?= e($item['name']) ?></strong></figcaption>
                </figure>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
</main>
<footer class="site-footer">
    <div class="container footer__grid">
        <div class="footer__brand">
            <a href="/" aria-label="Go Creative, inicio"><img src="/assets/img/logo.webp" width="620" height="224" alt="Go Creative Chile" loading="lazy"></a>
            <p>Desarrollo web, tiendas online y soluciones digitales para empresas que buscan vender más y operar con mayor eficiencia.</p>
            <div class="footer__social">
                <a href="https://www.facebook.com/profile.php?id=61572961960110" target="_blank" rel="noopener" aria-label="Facebook de Go Creative">f</a>
                <a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener" aria-label="WhatsApp de Go Creative">w</a>
            </div>
        </div>
        <div>
            <h2>Servicios</h2>
            <ul>
                <li><a href="/diseno-web-chile/">Diseño web</a></li>
                <li><a href="/tiendas-online/">Tiendas online</a></li>
                <li><a href="/software-a-medida/">Software a medida</a></li>
                <li><a href="/automatizacion/">Automatización</a></li>
                <li><a href="/meta-ads/">Campañas Meta Ads</a></li>
                <li><a href="/soporte-tecnico/">Soporte técnico</a></li>
            </ul>
        </div>
        <div>
            <h2>Empresa</h2>
            <ul>
                <li><a href="/nosotros/">Quiénes somos</a></li>
                <li><a href="/portafolio/">Portafolio</a></li>
                <li><a href="/servicios/">Todos los servicios</a></li>
                <li><a href="/contacto/">Contacto</a></li>
                <li><a href="/politica-de-privacidad/">Privacidad</a></li>
            </ul>
        </div>
        <div>
            <h2>Contacto</h2>
            <ul class="footer__contact">
                <li><span>Teléfono</span><a href="tel:<?= e(SITE_PHONE_LINK) ?>"><?= e(SITE_PHONE_DISPLAY) ?></a></li>
                <li><span>Correo</span><a href="mailto:<?= e(SITE_EMAIL) ?>"><?= e(SITE_EMAIL) ?></a></li>
                <li><span>Ubicación</span><?= e(SITE_CITY) ?></li>
            </ul>
        </div>
    </div>
    <div class="container footer__bottom">
        <p>© <span data-year><?= date('Y') ?></span> Go Creative Chile. Todos los derechos reservados.</p>
        <p>Diseño, estrategia y tecnología con propósito.</p>
    </div>
</footer>
<a class="whatsapp-float" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener" aria-label="Escribir a Go Creative por WhatsApp">
    <svg class="whatsapp-float__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.8a8.4 8.4 0 0 1-12.4 7.4L3.5 20.5l1.3-4.4A8.4 8.4 0 1 1 20.5 11.8Z"/><path d="M8.2 7.8c.2-.4.4-.4.7-.4h.4c.2 0 .4.1.5.4l.7 1.7c.1.3.1.5-.1.7l-.6.8c-.2.2-.1.4 0 .6.6 1.1 1.4 2 2.5 2.6.3.2.5.2.7 0l.9-1.1c.2-.2.4-.3.7-.2l1.7.8c.3.1.5.3.5.5 0 .3-.1 1.4-.7 2-.6.7-1.6 1-2.6.8-1.1-.2-2.5-.7-4.2-2.2-2-1.8-3.2-4-3.3-5.1-.1-.8.2-1.4.5-1.9Z"/></svg>
    <span>WhatsApp</span>
    <strong>Conversemos</strong>
</a>
<script src="/assets/js/main.js?v=2.1.0" defer></script>
</body>
</html>
