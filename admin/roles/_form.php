<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert"><strong>Revisa los siguientes datos:</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-xl-5">
            <section class="form-section">
                <div class="form-section__heading"><h2>Identidad del rol</h2><p>Un nombre claro facilita la administración futura.</p></div>
                <div class="mb-3">
                    <label class="form-label" for="name">NOMBRE</label>
                    <input class="form-control" id="name" name="name" value="<?= e($form['name']) ?>" maxlength="80" required autofocus<?= $isSystem ? ' readonly' : '' ?>>
                </div>
                <div>
                    <label class="form-label" for="description">DESCRIPCIÓN</label>
                    <textarea class="form-control" id="description" name="description" rows="4" maxlength="500" placeholder="Explica para quién está pensado este rol"><?= e($form['description']) ?></textarea>
                </div>
                <?php if ($isSystem): ?><div class="alert alert-info mt-3 mb-0 small">Este rol garantiza el acceso maestro y no puede cambiar de nombre ni perder permisos.</div><?php endif; ?>
            </section>
        </div>
        <div class="col-xl-7">
            <section class="form-section">
                <div class="form-section__heading"><h2>Permisos</h2><p>Selecciona exactamente las tareas que podrá realizar.</p></div>
                <?php foreach ($permissionGroups as $group => $groupPermissions): ?>
                    <h3 class="h6 fw-bold mt-3 mb-2"><?= e($group) ?></h3>
                    <div class="permission-grid mb-4">
                        <?php foreach ($groupPermissions as $permission): ?>
                            <label class="permission-item">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= (int) $permission['id'] ?>"<?= in_array((int) $permission['id'], $selectedPermissions, true) ? ' checked' : '' ?><?= $isSystem ? ' disabled' : '' ?>>
                                <span><strong><?= e($permission['name']) ?></strong><small><?= e($permission['description']) ?></small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar rol' : 'Crear rol' ?></button>
                    <a class="btn btn-outline-dark" href="<?= e(admin_url('roles/')) ?>">Cancelar</a>
                </div>
            </section>
        </div>
    </div>
</form>
