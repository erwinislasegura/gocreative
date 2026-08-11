<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$currentAdmin = require_auth();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    $statement = db()->prepare('SELECT password_hash FROM users WHERE id = :id');
    $statement->execute(['id' => $currentAdmin['id']]);
    $passwordHash = (string) $statement->fetchColumn();

    if (!password_verify($currentPassword, $passwordHash)) {
        $errors[] = 'La contraseña actual no es correcta.';
    }
    $errors = array_merge($errors, validate_password_strength($newPassword));
    if ($newPassword !== $confirmation) {
        $errors[] = 'La confirmación no coincide con la nueva contraseña.';
    }
    if ($newPassword !== '' && password_verify($newPassword, $passwordHash)) {
        $errors[] = 'La contraseña nueva debe ser distinta de la actual.';
    }

    if ($errors === []) {
        $update = db()->prepare(
            'UPDATE users
             SET password_hash = :password_hash, must_change_password = 0, password_changed_at = NOW()
             WHERE id = :id'
        );
        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $currentAdmin['id'],
        ]);
        audit_log('password_changed', 'user', (int) $currentAdmin['id'], 'Contraseña de acceso actualizada');
        flash('success', 'Tu contraseña fue actualizada correctamente.');
        redirect_admin();
    }
}

$pageTitle = 'Seguridad de la cuenta';
$activeMenu = '';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <span class="page-heading__eyebrow">Cuenta personal</span>
        <h1>Cambiar contraseña</h1>
        <p>Crea una clave única de al menos 12 caracteres para proteger el acceso administrativo.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <?php if ($errors !== []): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Revisa los siguientes datos:</strong>
                <ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= e(admin_url('cambiar-clave.php')) ?>">
            <?= csrf_field() ?>
            <section class="form-section">
                <div class="form-section__heading">
                    <h2>Actualizar credenciales</h2>
                    <p>La sesión actual seguirá abierta después del cambio.</p>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="current_password">CONTRASEÑA ACTUAL</label>
                    <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="new_password">NUEVA CONTRASEÑA</label>
                        <input class="form-control" id="new_password" name="new_password" type="password" autocomplete="new-password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">CONFIRMAR CONTRASEÑA</label>
                        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit">Guardar contraseña</button>
                    <?php if ((int) $currentAdmin['must_change_password'] === 0): ?>
                        <a class="btn btn-outline-dark" href="<?= e(admin_url()) ?>">Cancelar</a>
                    <?php endif; ?>
                </div>
            </section>
        </form>
    </div>
    <div class="col-xl-5">
        <section class="admin-card">
            <div class="admin-card__body">
                <span class="page-heading__eyebrow">Recomendación</span>
                <h2 class="h5 fw-bold">Una frase larga es mejor que una clave obvia.</h2>
                <p class="text-secondary mb-0">Combina mayúsculas, minúsculas, números y símbolos. No reutilices esta contraseña en otros servicios.</p>
            </div>
        </section>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
