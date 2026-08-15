<?php
$pageTitle = $pageTitle ?? 'Panel de control';
$activeMenu = $activeMenu ?? '';
$currentAdmin = $currentAdmin ?? current_user();
$flashes = pull_flashes();
?>
<!doctype html>
<html lang="es-CL">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#07111f">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Go Admin">
    <meta name="format-detection" content="telephone=no">
    <title><?= e($pageTitle) ?> | Go Creative</title>
    <link rel="icon" href="<?= e(site_path('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="icon" href="<?= e(site_path('/assets/img/favicon-32x32.png')) ?>" type="image/png" sizes="32x32">
    <link rel="shortcut icon" href="<?= e(site_path('/favicon.ico')) ?>">
    <link rel="apple-touch-icon" href="<?= e(admin_url('assets/img/app-icon-180.png?v=2.2.0')) ?>">
    <link rel="manifest" href="<?= e(admin_url('manifest.webmanifest?v=2.2.0')) ?>">
    <link rel="stylesheet" href="<?= e(admin_url('assets/vendor/bootstrap/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/admin.css?v=2.1.0')) ?>">
</head>
<body class="admin-body" data-admin-base="<?= e(admin_url()) ?>">
<div class="admin-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="admin-content">
        <header class="admin-topbar">
            <button class="admin-menu-button" type="button" data-admin-menu-toggle aria-expanded="false" aria-controls="adminSidebar" aria-label="Abrir navegación">
                <span></span><span></span><span></span>
            </button>
            <div>
                <span class="admin-topbar__eyebrow">Go Creative · Operaciones</span>
                <strong><?= e($pageTitle) ?></strong>
            </div>
            <?php if ($currentAdmin): ?>
                <div class="admin-app-actions ms-auto">
                    <span class="admin-network-status" data-network-status role="status" aria-live="polite">
                        <i aria-hidden="true"></i><span>En línea</span>
                    </span>
                    <button class="admin-install-button" type="button" data-pwa-install hidden aria-label="Instalar aplicación Go Creative">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v11m0 0 4-4m-4 4-4-4M5 15v4h14v-4"/></svg>
                        <span>Instalar</span>
                    </button>
                </div>
                <div class="dropdown">
                    <button class="admin-profile" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="admin-avatar"><?= e(initials($currentAdmin['name'])) ?></span>
                        <span class="admin-profile__copy"><strong><?= e($currentAdmin['name']) ?></strong><small><?= e($currentAdmin['role_name']) ?></small></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="<?= e(admin_url('cambiar-clave.php')) ?>">Cambiar contraseña</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= e(admin_url('logout.php')) ?>">Cerrar sesión</a></li>
                    </ul>
                </div>
            <?php endif; ?>
        </header>
        <main class="admin-main">
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endforeach; ?>
