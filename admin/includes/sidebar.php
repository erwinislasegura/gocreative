<aside class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar" aria-label="Navegación administrativa">
    <div class="offcanvas-header d-lg-none">
        <span class="text-white fw-bold">Navegación</span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Cerrar"></button>
    </div>
    <div class="admin-sidebar__inner">
        <a class="admin-brand" href="<?= e(admin_url()) ?>" aria-label="Go Creative, panel de control">
            <img src="<?= e(site_path('/assets/img/logo.webp')) ?>" width="620" height="224" alt="Go Creative Chile">
            <span>Panel de control</span>
        </a>
        <nav class="admin-nav" aria-label="Secciones del panel">
            <?php if (can('dashboard.view')): ?>
                <a class="<?= $activeMenu === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(admin_url()) ?>"><span>01</span>Resumen</a>
            <?php endif; ?>
            <?php if (can('users.view')): ?>
                <a class="<?= $activeMenu === 'users' ? 'is-active' : '' ?>" href="<?= e(admin_url('usuarios/')) ?>"><span>02</span>Usuarios</a>
            <?php endif; ?>
            <?php if (can('roles.view')): ?>
                <a class="<?= $activeMenu === 'roles' ? 'is-active' : '' ?>" href="<?= e(admin_url('roles/')) ?>"><span>03</span>Roles y permisos</a>
            <?php endif; ?>
        </nav>
        <div class="admin-sidebar__footer">
            <span class="admin-status"><i></i> Sistema protegido</span>
            <a href="<?= e(site_path('/')) ?>" target="_blank" rel="noopener">Ver sitio público ↗</a>
        </div>
    </div>
</aside>
