<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_permission('roles.view');
$roles = db()->query(
    'SELECT r.id, r.name, r.slug, r.description, r.is_system,
            COUNT(DISTINCT u.id) AS user_count,
            COUNT(DISTINCT rp.permission_id) AS permission_count
     FROM roles r
     LEFT JOIN users u ON u.role_id = r.id
     LEFT JOIN role_permissions rp ON rp.role_id = r.id
     GROUP BY r.id
     ORDER BY r.is_system DESC, r.name'
)->fetchAll();

$pageTitle = 'Roles y permisos';
$activeMenu = 'roles';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-heading">
    <div>
        <span class="page-heading__eyebrow">Gobierno de acceso</span>
        <h1>Roles y permisos</h1>
        <p>Define responsabilidades claras sin entregar más acceso del necesario.</p>
    </div>
    <?php if (can('roles.create')): ?><a class="btn btn-primary" href="<?= e(admin_url('roles/crear.php')) ?>">+ Nuevo rol</a><?php endif; ?>
</div>

<div class="row g-3">
    <?php foreach ($roles as $role): ?>
        <div class="col-md-6 col-xl-4">
            <article class="admin-card">
                <div class="admin-card__body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                        <div><span class="page-heading__eyebrow"><?= (int) $role['is_system'] === 1 ? 'Rol protegido' : 'Rol personalizado' ?></span><h2 class="h5 fw-bold mb-1"><?= e($role['name']) ?></h2><small class="text-secondary">/<?= e($role['slug']) ?></small></div>
                        <span class="admin-avatar"><?= str_pad((string) (int) $role['user_count'], 2, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <p class="text-secondary role-description"><?= e($role['description'] ?: 'Sin descripción definida.') ?></p>
                    <div class="d-flex gap-2 flex-wrap mb-4"><span class="role-badge"><?= (int) $role['user_count'] ?> usuarios</span><span class="role-badge"><?= (int) $role['permission_count'] ?> permisos</span></div>
                    <div class="d-flex gap-2">
                        <?php if (can('roles.edit')): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(admin_url('roles/editar.php?id=' . (int) $role['id'])) ?>">Configurar</a><?php endif; ?>
                        <?php if (can('roles.delete') && (int) $role['is_system'] === 0): ?>
                            <form method="post" action="<?= e(admin_url('roles/accion.php')) ?>" data-confirm="¿Eliminar este rol? Solo es posible si no tiene usuarios asignados.">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $role['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
