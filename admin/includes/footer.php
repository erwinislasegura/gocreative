        </main>
        <footer class="admin-footer">
            <span>© <?= date('Y') ?> Go Creative Chile</span>
            <span>Panel privado · no indexado</span>
        </footer>
    </div>
</div>
<?php if ($currentAdmin): ?>
<nav class="admin-bottom-nav" aria-label="Accesos principales">
    <?php if (can('dashboard.view')): ?>
        <a class="<?= $activeMenu === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(admin_url()) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11 12 4l8 7v9h-6v-6h-4v6H4z"/></svg>
            <span>Inicio</span>
        </a>
    <?php endif; ?>
    <?php if (can('quotes.view')): ?>
        <a class="<?= $activeMenu === 'quotes' ? 'is-active' : '' ?>" href="<?= e(admin_url('cotizaciones/')) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6zM15 3v5h4M9 12h7M9 16h7"/></svg>
            <span>Cotizar</span>
        </a>
    <?php endif; ?>
    <?php if (can('hosting.view')): ?>
        <a class="<?= $activeMenu === 'hosting' ? 'is-active' : '' ?>" href="<?= e(admin_url('hosting/')) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v5H4zm0 9h16v5H4zM7 7.5h.01M7 16.5h.01"/></svg>
            <span>Hosting</span>
        </a>
    <?php endif; ?>
    <button type="button" data-admin-menu-toggle aria-expanded="false" aria-controls="adminSidebar" aria-label="Abrir menú completo">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14M5 12h14M5 17h14"/></svg>
        <span>Menú</span>
    </button>
</nav>
<?php endif; ?>
<div class="admin-update-bar" data-app-update hidden role="status">
    <span>Hay una nueva versión de la aplicación.</span>
    <button type="button" data-app-update-action>Actualizar</button>
</div>
<script src="<?= e(admin_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(admin_url('assets/js/admin.js?v=2.0.0')) ?>"></script>
<?php foreach (($pageScripts ?? []) as $pageScript): ?>
<script src="<?= e(admin_url('assets/js/' . ltrim((string) $pageScript, '/'))) ?>"></script>
<?php endforeach; ?>
</body>
</html>
