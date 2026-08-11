<?php if ($errors !== []): ?><div class="alert alert-danger"><strong>Revisa los datos:</strong><ul class="mb-0 mt-2"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="<?= e($formAction) ?>">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-xl-8">
            <section class="form-section">
                <div class="form-section__heading"><h2>Cliente</h2><p>Si el correo ya existe, actualizaremos sus datos sin duplicarlo.</p></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="customer_name">NOMBRE *</label><input class="form-control" id="customer_name" name="customer_name" value="<?= e($form['customer_name']) ?>" maxlength="100" required autofocus></div>
                    <div class="col-md-6"><label class="form-label" for="customer_email">CORREO *</label><input class="form-control" id="customer_email" name="customer_email" type="email" value="<?= e($form['customer_email']) ?>" maxlength="190" required></div>
                    <div class="col-md-6"><label class="form-label" for="customer_company">EMPRESA</label><input class="form-control" id="customer_company" name="customer_company" value="<?= e($form['customer_company']) ?>" maxlength="150"></div>
                    <div class="col-md-6"><label class="form-label" for="customer_phone">TELÉFONO</label><input class="form-control" id="customer_phone" name="customer_phone" value="<?= e($form['customer_phone']) ?>" maxlength="30"></div>
                </div>
            </section>
            <section class="form-section">
                <div class="form-section__heading"><h2>Servicio y renovación</h2><p>La fecha se destacará en rojo automáticamente cuando esté vencida.</p></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="service_name">NOMBRE DEL SERVICIO *</label><input class="form-control" id="service_name" name="service_name" value="<?= e($form['service_name']) ?>" maxlength="150" placeholder="Hosting administrado" required></div>
                    <div class="col-md-6"><label class="form-label" for="domain">DOMINIO</label><input class="form-control" id="domain" name="domain" value="<?= e($form['domain']) ?>" maxlength="190" placeholder="cliente.cl"></div>
                    <div class="col-md-6"><label class="form-label" for="plan_name">PLAN *</label><input class="form-control" id="plan_name" name="plan_name" value="<?= e($form['plan_name']) ?>" maxlength="120" placeholder="Hosting Business" required></div>
                    <div class="col-md-6"><label class="form-label" for="billing_cycle">CICLO *</label><select class="form-select" id="billing_cycle" name="billing_cycle"><option value="semiannual"<?= $form['billing_cycle'] === 'semiannual' ? ' selected' : '' ?>>Semestral · 6 meses</option><option value="annual"<?= $form['billing_cycle'] === 'annual' ? ' selected' : '' ?>>Anual · 12 meses</option></select></div>
                    <div class="col-md-4"><label class="form-label" for="start_date">INICIO *</label><input class="form-control" id="start_date" name="start_date" type="date" value="<?= e($form['start_date']) ?>" required></div>
                    <div class="col-md-4"><label class="form-label" for="due_date">PRÓXIMO VENCIMIENTO *</label><input class="form-control" id="due_date" name="due_date" type="date" value="<?= e($form['due_date']) ?>" required></div>
                    <div class="col-md-4"><label class="form-label" for="amount">VALOR RENOVACIÓN *</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" id="amount" name="amount" type="number" min="100" max="100000000" step="1" value="<?= e($form['amount']) ?>" required><span class="input-group-text">CLP</span></div></div>
                    <div class="col-md-6"><label class="form-label" for="status">ESTADO</label><select class="form-select" id="status" name="status"><option value="active"<?= $form['status'] === 'active' ? ' selected' : '' ?>>Activo</option><option value="suspended"<?= $form['status'] === 'suspended' ? ' selected' : '' ?>>Suspendido</option><option value="cancelled"<?= $form['status'] === 'cancelled' ? ' selected' : '' ?>>Cancelado</option></select></div>
                    <div class="col-12"><label class="form-label" for="notes">NOTAS INTERNAS</label><textarea class="form-control" id="notes" name="notes" rows="4" maxlength="1500"><?= e($form['notes']) ?></textarea></div>
                </div>
            </section>
        </div>
        <div class="col-xl-4"><section class="admin-card payment-summary-card"><div class="admin-card__body"><span class="page-heading__eyebrow">Control comercial</span><h2 class="h5 fw-bold">Renovaciones visibles.</h2><p class="text-secondary">Los avisos de cobro y el enlace Flow se generan desde el detalle del servicio.</p><div class="d-grid gap-2 mt-4"><button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Registrar hosting' ?></button><a class="btn btn-outline-dark" href="<?= e(admin_url('hosting/')) ?>">Cancelar</a></div></div></section></div>
    </div>
</form>
