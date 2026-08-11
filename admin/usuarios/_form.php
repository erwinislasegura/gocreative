<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">
        <strong>Revisa los siguientes datos:</strong>
        <ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-xl-8">
            <section class="form-section">
                <div class="form-section__heading"><h2>Datos de acceso</h2><p>Información principal para identificar al usuario.</p></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">NOMBRE COMPLETO</label>
                        <input class="form-control" id="name" name="name" value="<?= e($form['name']) ?>" maxlength="100" autocomplete="name" required autofocus>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">CORREO ELECTRÓNICO</label>
                        <input class="form-control" id="email" name="email" type="email" value="<?= e($form['email']) ?>" maxlength="190" autocomplete="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="role_id">ROL</label>
                        <select class="form-select" id="role_id" name="role_id" required<?= $lockOwnAccess ? ' disabled' : '' ?>>
                            <option value="">Selecciona un rol</option>
                            <?php foreach ($roles as $role): ?><option value="<?= (int) $role['id'] ?>"<?= (int) $form['role_id'] === (int) $role['id'] ? ' selected' : '' ?>><?= e($role['name']) ?></option><?php endforeach; ?>
                        </select>
                        <?php if ($lockOwnAccess): ?><input type="hidden" name="role_id" value="<?= (int) $form['role_id'] ?>"><div class="form-text">No puedes cambiar tu propio nivel de acceso.</div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="status">ESTADO</label>
                        <select class="form-select" id="status" name="status"<?= $lockOwnAccess ? ' disabled' : '' ?>>
                            <option value="active"<?= $form['status'] === 'active' ? ' selected' : '' ?>>Activo</option>
                            <option value="inactive"<?= $form['status'] === 'inactive' ? ' selected' : '' ?>>Inactivo</option>
                        </select>
                        <?php if ($lockOwnAccess): ?><input type="hidden" name="status" value="active"><div class="form-text">Tu propia cuenta debe permanecer activa.</div><?php endif; ?>
                    </div>
                </div>
            </section>
            <section class="form-section">
                <div class="form-section__heading"><h2><?= $isEdit ? 'Restablecer contraseña' : 'Contraseña temporal' ?></h2><p><?= $isEdit ? 'Déjala vacía para conservar la contraseña actual.' : 'El usuario deberá reemplazarla durante su primer ingreso.' ?></p></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="password"><?= $isEdit ? 'NUEVA CONTRASEÑA (OPCIONAL)' : 'CONTRASEÑA' ?></label>
                        <input class="form-control" id="password" name="password" type="password" autocomplete="new-password"<?= $isEdit ? '' : ' required' ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">CONFIRMAR CONTRASEÑA</label>
                        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"<?= $isEdit ? '' : ' required' ?>>
                    </div>
                </div>
                <div class="form-text mt-2">Mínimo 12 caracteres, con mayúsculas, minúsculas, número y símbolo.</div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="admin-card">
                <div class="admin-card__body">
                    <span class="page-heading__eyebrow">Control de acceso</span>
                    <h2 class="h5 fw-bold">El rol define el alcance.</h2>
                    <p class="text-secondary">Asigna únicamente los permisos necesarios. Puedes modificar los roles desde su sección.</p>
                    <div class="d-grid gap-2 mt-4">
                        <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?></button>
                        <a class="btn btn-outline-dark" href="<?= e(admin_url('usuarios/')) ?>">Cancelar</a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</form>
