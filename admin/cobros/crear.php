<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/app/Payments/payment_helpers.php';

$currentAdmin = require_permission('payments.create');
$form = ['customer_name' => '', 'customer_email' => '', 'subject' => '', 'amount' => ''];
$errors = [];
$flowReady = flow_is_configured();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $form = [
        'customer_name' => trim((string) ($_POST['customer_name'] ?? '')),
        'customer_email' => mb_strtolower(trim((string) ($_POST['customer_email'] ?? ''))),
        'subject' => trim((string) ($_POST['subject'] ?? '')),
        'amount' => trim((string) ($_POST['amount'] ?? '')),
    ];

    if (!$flowReady) $errors[] = 'Configura Flow antes de crear el primer cobro.';
    if (mb_strlen($form['customer_name']) < 2 || mb_strlen($form['customer_name']) > 100) $errors[] = 'El nombre debe tener entre 2 y 100 caracteres.';
    if (!filter_var($form['customer_email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['customer_email']) > 190) $errors[] = 'Ingresa un correo electronico valido.';
    if (mb_strlen($form['subject']) < 5 || mb_strlen($form['subject']) > 180) $errors[] = 'El concepto debe tener entre 5 y 180 caracteres.';
    if (!preg_match('/^[0-9]+$/', $form['amount'])) $errors[] = 'Ingresa el monto en pesos, sin puntos ni simbolos.';
    $amount = (int) $form['amount'];
    if ($amount < 100 || $amount > 100000000) $errors[] = 'El monto debe estar entre $100 y $100.000.000 CLP.';

    if ($errors === []) {
        try {
            $orderId = payment_create_order([
                'customer_name' => $form['customer_name'],
                'customer_email' => $form['customer_email'],
                'subject' => $form['subject'],
                'amount' => $amount,
            ], (int) $currentAdmin['id']);
            audit_log('created', 'payment_order', $orderId, 'Cobro Flow creado por ' . payment_format_amount($amount));
            flash('success', 'Cobro creado. El enlace ya esta listo para compartir.');
            redirect_admin('cobros/ver.php?id=' . $orderId);
        } catch (Throwable $exception) {
            error_log('No se pudo crear el cobro Flow: ' . $exception->getMessage());
            $errors[] = $exception instanceof PDOException
                ? 'No se pudo guardar el cobro. Revisa que la migracion de Flow este importada.'
                : payment_safe_error($exception->getMessage());
        }
    }
}

$pageTitle = 'Crear cobro';
$activeMenu = 'payments';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading"><div><span class="page-heading__eyebrow">Cobros Flow</span><h1>Nuevo enlace de pago</h1><p>Define el cliente, el concepto y el monto. Flow se encargara del checkout y de los medios de pago.</p></div></div>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert"><strong>Revisa antes de continuar:</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="<?= e(admin_url('cobros/crear.php')) ?>">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-xl-8">
            <section class="form-section">
                <div class="form-section__heading"><h2>Datos del cobro</h2><p>Esta informacion identifica la orden dentro del panel y en Flow.</p></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="customer_name">NOMBRE DEL CLIENTE</label><input class="form-control" id="customer_name" name="customer_name" value="<?= e($form['customer_name']) ?>" maxlength="100" autocomplete="name" required autofocus></div>
                    <div class="col-md-6"><label class="form-label" for="customer_email">CORREO DEL PAGADOR</label><input class="form-control" id="customer_email" name="customer_email" type="email" value="<?= e($form['customer_email']) ?>" maxlength="190" autocomplete="email" required><div class="form-text">Flow usara este correo para informar el resultado.</div></div>
                    <div class="col-12"><label class="form-label" for="subject">CONCEPTO</label><input class="form-control" id="subject" name="subject" value="<?= e($form['subject']) ?>" maxlength="180" placeholder="Ej.: Abono desarrollo sitio web corporativo" required></div>
                    <div class="col-md-6"><label class="form-label" for="amount">MONTO CLP</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" id="amount" name="amount" type="number" min="100" max="100000000" step="1" value="<?= e($form['amount']) ?>" inputmode="numeric" placeholder="250000" required><span class="input-group-text">CLP</span></div><div class="form-text">Escribe el monto sin puntos ni simbolos.</div></div>
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="admin-card payment-summary-card">
                <div class="admin-card__body">
                    <span class="page-heading__eyebrow">Checkout seguro</span>
                    <h2 class="h5 fw-bold">Go Creative + Flow</h2>
                    <p class="text-secondary">El enlace llevara al cliente al checkout de Flow. El proyecto no solicita ni almacena datos de tarjetas.</p>
                    <ul class="payment-checklist"><li>Firma HMAC SHA-256</li><li>Confirmacion automatica</li><li>Consulta manual de estado</li><li>Registro de actividad</li></ul>
                    <div class="d-grid gap-2 mt-4"><button class="btn btn-primary" type="submit"<?= $flowReady ? '' : ' disabled' ?>>Crear enlace de pago</button><a class="btn btn-outline-dark" href="<?= e(admin_url('cobros/')) ?>">Cancelar</a></div>
                </div>
            </section>
        </div>
    </div>
</form>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
