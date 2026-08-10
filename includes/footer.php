<?php
$showProjectStrip = !in_array(
    $meta['path'],
    ['/', '/portafolio/', '/politica-de-privacidad/', '/404'],
    true
);
?>
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
            <a href="/" aria-label="Go Creative, inicio"><img src="/assets/img/logo-white.webp" width="250" height="141" alt="Go Creative Chile" loading="lazy"></a>
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
    <span>WhatsApp</span>
    <strong>Conversemos</strong>
</a>
<script src="/assets/js/main.js?v=1.1.0" defer></script>
</body>
</html>
