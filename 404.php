<?php
http_response_code(404);
$meta = ['title' => 'Página no encontrada | Go Creative', 'description' => 'La página solicitada no existe o cambió de dirección.', 'path' => '/404'];
$active = '';
require __DIR__ . '/includes/header.php';
?>
<section class="inner-hero" style="min-height:72vh;display:flex;align-items:center"><div class="container"><p class="eyebrow"><span></span> Error 404</p><h1>Esta página no está <em>disponible.</em></h1><p class="inner-hero__lead">Puede haber cambiado de dirección o el enlace está incompleto.</p><a class="button button--lime" href="/">Volver al inicio <span>→</span></a></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
