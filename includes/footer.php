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
                <a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener" aria-label="WhatsApp de Go Creative"><svg class="footer-social__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.077 4.487.709.306 1.262.489 1.693.625.712.227 1.36.195 1.871.118.57-.085 1.758-.719 2.006-1.413.248-.694.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.981.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.82 9.82 0 0 1 7.021 2.91 9.83 9.83 0 0 1 2.9 7.024c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.82 11.82 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.14 1.588 5.945L.057 24l6.3-1.654a11.88 11.88 0 0 0 5.69 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.82 11.82 0 0 0-3.48-8.413Z"/></svg></a>
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
    <svg class="whatsapp-float__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.077 4.487.709.306 1.262.489 1.693.625.712.227 1.36.195 1.871.118.57-.085 1.758-.719 2.006-1.413.248-.694.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.981.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.82 9.82 0 0 1 7.021 2.91 9.83 9.83 0 0 1 2.9 7.024c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.82 11.82 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.14 1.588 5.945L.057 24l6.3-1.654a11.88 11.88 0 0 0 5.69 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.82 11.82 0 0 0-3.48-8.413Z"/></svg>
    <span>WhatsApp</span>
    <strong>Conversemos</strong>
</a>
<script src="/assets/js/main.js?v=2.2.0" defer></script>
</body>
</html>
