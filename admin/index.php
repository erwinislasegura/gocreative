<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$currentAdmin = require_permission('dashboard.view');

function dashboard_table_exists(string $table): bool
{
    static $known = [];
    if (array_key_exists($table, $known)) {
        return $known[$table];
    }

    $statement = db()->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
    );
    $statement->execute(['table_name' => $table]);
    return $known[$table] = (int) $statement->fetchColumn() > 0;
}

function dashboard_money(int $amount, string $currency = 'CLP'): string
{
    return ($currency === 'CLP' ? '$' : $currency . ' ') . number_format($amount, 0, ',', '.');
}

function dashboard_short_date(?string $date): string
{
    if (!$date) {
        return 'Sin fecha';
    }

    try {
        return (new DateTimeImmutable($date))->format('d-m-Y');
    } catch (Throwable $exception) {
        return 'Sin fecha';
    }
}

function dashboard_quote_meta(string $status): array
{
    $statuses = [
        'draft' => ['label' => 'Borrador', 'class' => 'created'],
        'sent' => ['label' => 'Enviada', 'class' => 'pending'],
        'accepted' => ['label' => 'Aceptada', 'class' => 'paid'],
        'rejected' => ['label' => 'Rechazada', 'class' => 'rejected'],
        'expired' => ['label' => 'Vencida', 'class' => 'cancelled'],
    ];
    return $statuses[$status] ?? $statuses['draft'];
}

function dashboard_due_meta(int $days): array
{
    if ($days < 0) {
        return ['label' => 'Vencido hace ' . abs($days) . ' días', 'class' => 'overdue'];
    }
    if ($days === 0) {
        return ['label' => 'Vence hoy', 'class' => 'warning'];
    }
    return ['label' => 'Vence en ' . $days . ' días', 'class' => 'warning'];
}

$securityStats = [
    'active_users' => (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
    'logins_today' => (int) db()->query("SELECT COUNT(*) FROM login_attempts WHERE successful = 1 AND DATE(attempted_at) = CURDATE()")->fetchColumn(),
    'failed_logins' => (int) db()->query("SELECT COUNT(*) FROM login_attempts WHERE successful = 0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn(),
];

$quotesReady = can('quotes.view') && dashboard_table_exists('quotes') && dashboard_table_exists('customers');
$hostingReady = can('hosting.view') && dashboard_table_exists('hosting_services') && dashboard_table_exists('customers');
$paymentsReady = $hostingReady && dashboard_table_exists('payment_orders');
$commercialError = false;

$quoteStats = [
    'open_count' => 0,
    'draft_count' => 0,
    'sent_count' => 0,
    'accepted_count' => 0,
    'rejected_count' => 0,
    'expired_count' => 0,
    'pipeline' => 0,
    'accepted_month' => 0,
    'conversion' => 0,
];
$hostingStats = [
    'active_count' => 0,
    'overdue_count' => 0,
    'upcoming_count' => 0,
    'upcoming_amount' => 0,
    'portfolio' => 0,
];
$paymentStats = ['paid_month' => 0, 'pending_count' => 0, 'pending_amount' => 0];
$recentQuotes = [];
$hostingActions = [];

try {
    if ($quotesReady) {
        $quoteStatsRaw = db()->query(
            "SELECT
                SUM(status IN ('draft', 'sent') AND valid_until >= CURDATE()) AS open_count,
                SUM(status = 'draft' AND valid_until >= CURDATE()) AS draft_count,
                SUM(status = 'sent' AND valid_until >= CURDATE()) AS sent_count,
                SUM(status = 'accepted') AS accepted_count,
                SUM(status = 'rejected') AS rejected_count,
                SUM(status = 'expired' OR (status IN ('draft', 'sent') AND valid_until < CURDATE())) AS expired_count,
                COALESCE(SUM(CASE WHEN status = 'sent' AND valid_until >= CURDATE() THEN total ELSE 0 END), 0) AS pipeline,
                COALESCE(SUM(CASE WHEN status = 'accepted' AND COALESCE(responded_at, updated_at) >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN total ELSE 0 END), 0) AS accepted_month
             FROM quotes"
        )->fetch() ?: [];

        foreach (array_keys($quoteStats) as $key) {
            if ($key !== 'conversion' && array_key_exists($key, $quoteStatsRaw)) {
                $quoteStats[$key] = (int) $quoteStatsRaw[$key];
            }
        }
        $decidedQuotes = $quoteStats['accepted_count'] + $quoteStats['rejected_count'];
        $quoteStats['conversion'] = $decidedQuotes > 0
            ? (int) round(($quoteStats['accepted_count'] / $decidedQuotes) * 100)
            : 0;

        $recentQuotes = db()->query(
            "SELECT q.id, q.quote_number, q.title, q.total, q.currency,
                    CASE WHEN q.status IN ('draft', 'sent') AND q.valid_until < CURDATE() THEN 'expired' ELSE q.status END AS status,
                    q.valid_until,
                    q.created_at, q.sent_at, c.name AS customer_name, c.company AS customer_company,
                    DATEDIFF(CURDATE(), COALESCE(q.sent_at, q.created_at)) AS waiting_days
             FROM quotes q
             INNER JOIN customers c ON c.id = q.customer_id
             ORDER BY FIELD(CASE WHEN q.status IN ('draft', 'sent') AND q.valid_until < CURDATE() THEN 'expired' ELSE q.status END,
                            'sent', 'draft', 'accepted', 'rejected', 'expired'), q.updated_at DESC
             LIMIT 6"
        )->fetchAll();
    }

    if ($hostingReady) {
        $hostingStatsRaw = db()->query(
            "SELECT
                SUM(status = 'active') AS active_count,
                SUM(status = 'active' AND due_date < CURDATE()) AS overdue_count,
                SUM(status = 'active' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) AS upcoming_count,
                COALESCE(SUM(CASE WHEN status = 'active' AND due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN amount ELSE 0 END), 0) AS upcoming_amount,
                COALESCE(SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END), 0) AS portfolio
             FROM hosting_services"
        )->fetch() ?: [];
        foreach (array_keys($hostingStats) as $key) {
            if (array_key_exists($key, $hostingStatsRaw)) {
                $hostingStats[$key] = (int) $hostingStatsRaw[$key];
            }
        }

        $hostingActions = db()->query(
            "SELECT hs.id, hs.service_name, hs.domain, hs.plan_name, hs.amount, hs.currency,
                    hs.due_date, hs.last_notice_level, DATEDIFF(hs.due_date, CURDATE()) AS days_until_due,
                    c.name AS customer_name, c.email AS customer_email
             FROM hosting_services hs
             INNER JOIN customers c ON c.id = hs.customer_id
             WHERE hs.status = 'active' AND hs.due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY hs.due_date ASC
             LIMIT 6"
        )->fetchAll();
    }

    if ($paymentsReady) {
        $paymentStatsRaw = db()->query(
            "SELECT
                COALESCE(SUM(CASE WHEN status = 'paid' AND paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN amount ELSE 0 END), 0) AS paid_month,
                SUM(status = 'pending' AND (expires_at IS NULL OR expires_at >= NOW())) AS pending_count,
                COALESCE(SUM(CASE WHEN status = 'pending' AND (expires_at IS NULL OR expires_at >= NOW()) THEN amount ELSE 0 END), 0) AS pending_amount
             FROM payment_orders
             WHERE reference_type = 'hosting'"
        )->fetch() ?: [];
        foreach (array_keys($paymentStats) as $key) {
            if (array_key_exists($key, $paymentStatsRaw)) {
                $paymentStats[$key] = (int) $paymentStatsRaw[$key];
            }
        }
    }
} catch (Throwable $exception) {
    error_log('No fue posible cargar las métricas comerciales del dashboard: ' . $exception->getMessage());
    $commercialError = true;
}

$recentActivity = db()->query(
    'SELECT a.action, a.description, a.created_at, u.name AS user_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC
     LIMIT 5'
)->fetchAll();

$metricCards = [];
if ($quotesReady && !$commercialError) {
    $metricCards[] = [
        'label' => 'Propuestas abiertas',
        'value' => (string) $quoteStats['open_count'],
        'note' => $quoteStats['sent_count'] . ' enviadas · ' . $quoteStats['draft_count'] . ' borradores',
        'tone' => 'dark',
    ];
}
if ($hostingReady && !$commercialError) {
    $metricCards[] = [
        'label' => 'Hosting vencido',
        'value' => (string) $hostingStats['overdue_count'],
        'note' => $hostingStats['upcoming_count'] . ' vencen en 30 días',
        'tone' => $hostingStats['overdue_count'] > 0 ? 'danger' : '',
    ];
}
if ($paymentsReady && !$commercialError) {
    $metricCards[] = [
        'label' => 'Ingresos del mes',
        'value' => dashboard_money($paymentStats['paid_month']),
        'note' => $paymentStats['pending_count'] . ' cobros pendientes',
        'tone' => 'money',
    ];
}
if ($hostingReady && !$commercialError) {
    $metricCards[] = [
        'label' => 'Cartera de hosting',
        'value' => dashboard_money($hostingStats['portfolio']),
        'note' => $hostingStats['active_count'] . ' servicios activos',
        'tone' => 'money',
    ];
}
if ($metricCards === []) {
    $metricCards = [
        ['label' => 'Usuarios activos', 'value' => (string) $securityStats['active_users'], 'note' => 'Con acceso vigente', 'tone' => 'dark'],
        ['label' => 'Ingresos hoy', 'value' => (string) $securityStats['logins_today'], 'note' => 'Sesiones correctas', 'tone' => ''],
        ['label' => 'Alertas 24 h', 'value' => (string) $securityStats['failed_logins'], 'note' => 'Intentos rechazados', 'tone' => $securityStats['failed_logins'] > 0 ? 'danger' : ''],
    ];
}

$months = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$todayLabel = date('j') . ' de ' . $months[(int) date('n')] . ' de ' . date('Y');
$firstName = explode(' ', trim((string) $currentAdmin['name']))[0];
$pageTitle = 'Centro de operación';
$activeMenu = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="dashboard-command">
    <div class="dashboard-command__copy">
        <span class="dashboard-command__date"><?= e($todayLabel) ?></span>
        <h1>Hola, <?= e($firstName) ?>.</h1>
        <?php if ($quotesReady && !$commercialError): ?>
            <p>Tienes <strong><?= $quoteStats['sent_count'] ?> propuestas enviadas</strong> esperando respuesta por un total de <strong><?= e(dashboard_money($quoteStats['pipeline'])) ?></strong>.</p>
        <?php else: ?>
            <p>Revisa las prioridades del equipo y continúa con las tareas más importantes del día.</p>
        <?php endif; ?>
    </div>
    <div class="dashboard-command__signals">
        <?php if ($quotesReady && !$commercialError): ?>
            <div><span>Aceptado este mes</span><strong><?= e(dashboard_money($quoteStats['accepted_month'])) ?></strong></div>
            <div><span>Conversión decidida</span><strong><?= $quoteStats['conversion'] ?>%</strong></div>
        <?php endif; ?>
        <?php if ($hostingReady && !$commercialError): ?>
            <div><span>Renovaciones próximas</span><strong><?= e(dashboard_money($hostingStats['upcoming_amount'])) ?></strong></div>
        <?php endif; ?>
    </div>
</section>

<nav class="dashboard-quick-actions" aria-label="Acciones rápidas">
    <?php if (can('quotes.create')): ?>
        <a class="dashboard-quick-action dashboard-quick-action--primary" href="<?= e(admin_url('cotizaciones/crear.php')) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            <span><strong>Nueva cotización</strong><small>Crear una propuesta</small></span>
        </a>
    <?php endif; ?>
    <?php if (can('hosting.create')): ?>
        <a class="dashboard-quick-action" href="<?= e(admin_url('hosting/crear.php')) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v5H4zm0 9h16v5H4zM7 7.5h.01M7 16.5h.01"/></svg>
            <span><strong>Registrar hosting</strong><small>Nuevo ciclo de cobro</small></span>
        </a>
    <?php endif; ?>
    <?php if (can('quotes.view')): ?>
        <a class="dashboard-quick-action" href="<?= e(admin_url('cotizaciones/')) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6zM15 3v5h4M9 12h7M9 16h7"/></svg>
            <span><strong>Ver cotizaciones</strong><small>Seguimiento comercial</small></span>
        </a>
    <?php endif; ?>
    <a class="dashboard-quick-action" href="<?= e(site_path('/')) ?>" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 5h5v5M19 5l-8 8M18 13v6H5V6h6"/></svg>
        <span><strong>Ver sitio web</strong><small>Abrir en otra pestaña</small></span>
    </a>
</nav>

<?php if ($commercialError || ((can('quotes.view') || can('hosting.view')) && !$quotesReady && !$hostingReady)): ?>
    <div class="alert alert-warning" role="alert">No fue posible cargar los módulos comerciales. Verifica que las migraciones de cotizaciones, hosting y Flow estén aplicadas.</div>
<?php endif; ?>

<div class="dashboard-metrics">
    <?php foreach ($metricCards as $metric): ?>
        <section class="admin-card stat-card <?= $metric['tone'] === 'dark' ? 'stat-card--dark' : '' ?> <?= $metric['tone'] === 'danger' ? 'stat-card--danger' : '' ?>">
            <span class="stat-card__label"><?= e($metric['label']) ?></span>
            <strong class="stat-card__value <?= $metric['tone'] === 'money' ? 'stat-card__value--money' : '' ?>"><?= e($metric['value']) ?></strong>
            <span class="stat-card__note"><?= e($metric['note']) ?></span>
        </section>
    <?php endforeach; ?>
</div>

<div class="dashboard-work-grid">
    <?php if ($quotesReady && !$commercialError): ?>
        <section class="admin-card dashboard-panel">
            <div class="admin-card__header">
                <div><h2>Cotizaciones prioritarias</h2><span class="admin-card__subtitle">Enviadas y recientes primero</span></div>
                <a class="btn btn-sm btn-outline-dark" href="<?= e(admin_url('cotizaciones/')) ?>">Ver todas</a>
            </div>
            <div class="dashboard-list">
                <?php if ($recentQuotes === []): ?>
                    <div class="dashboard-list__empty"><strong>Aún no hay cotizaciones</strong><span>Crea la primera propuesta para iniciar el seguimiento.</span></div>
                <?php endif; ?>
                <?php foreach ($recentQuotes as $quote): $statusMeta = dashboard_quote_meta((string) $quote['status']); ?>
                    <article class="dashboard-list-item">
                        <span class="user-cell__avatar"><?= e(initials($quote['customer_name'])) ?></span>
                        <div class="dashboard-list-item__main">
                            <span class="dashboard-list-item__eyebrow"><?= e($quote['quote_number']) ?> · <?= e($quote['customer_company'] ?: $quote['customer_name']) ?></span>
                            <strong><?= e($quote['title']) ?></strong>
                            <small>
                                <?php if ($quote['status'] === 'sent'): ?>Esperando respuesta hace <?= max(0, (int) $quote['waiting_days']) ?> días<?php else: ?>Válida hasta <?= e(dashboard_short_date($quote['valid_until'])) ?><?php endif; ?>
                            </small>
                        </div>
                        <div class="dashboard-list-item__value">
                            <strong><?= e(dashboard_money((int) $quote['total'], (string) $quote['currency'])) ?></strong>
                            <span class="payment-status payment-status--<?= e($statusMeta['class']) ?>"><?= e($statusMeta['label']) ?></span>
                        </div>
                        <a class="dashboard-list-item__action" href="<?= e(admin_url('cotizaciones/ver.php?id=' . (int) $quote['id'])) ?>" aria-label="Gestionar cotización <?= e($quote['quote_number']) ?>">→</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($hostingReady && !$commercialError): ?>
        <section class="admin-card dashboard-panel">
            <div class="admin-card__header">
                <div><h2>Renovaciones prioritarias</h2><span class="admin-card__subtitle">Vencidas y próximas 30 días</span></div>
                <a class="btn btn-sm btn-outline-dark" href="<?= e(admin_url('hosting/')) ?>">Ver hosting</a>
            </div>
            <div class="dashboard-list">
                <?php if ($hostingActions === []): ?>
                    <div class="dashboard-list__empty dashboard-list__empty--success"><strong>Todo está al día</strong><span>No hay renovaciones próximas durante los siguientes 30 días.</span></div>
                <?php endif; ?>
                <?php foreach ($hostingActions as $service): $dueMeta = dashboard_due_meta((int) $service['days_until_due']); ?>
                    <article class="dashboard-list-item">
                        <span class="dashboard-service-icon">H</span>
                        <div class="dashboard-list-item__main">
                            <span class="dashboard-list-item__eyebrow"><?= e($service['customer_name']) ?></span>
                            <strong><?= e($service['domain'] ?: $service['service_name']) ?></strong>
                            <small><?= e($service['plan_name']) ?> · aviso <?= (int) $service['last_notice_level'] ?> de 3</small>
                        </div>
                        <div class="dashboard-list-item__value">
                            <strong><?= e(dashboard_money((int) $service['amount'], (string) $service['currency'])) ?></strong>
                            <span class="due-badge due-badge--<?= e($dueMeta['class']) ?>"><?= e($dueMeta['label']) ?></span>
                        </div>
                        <a class="dashboard-list-item__action" href="<?= e(admin_url('hosting/ver.php?id=' . (int) $service['id'])) ?>" aria-label="Gestionar hosting <?= e($service['domain'] ?: $service['service_name']) ?>">→</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<div class="dashboard-secondary-grid">
    <section class="admin-card">
        <div class="admin-card__header"><div><h2>Actividad reciente</h2><span class="admin-card__subtitle">Últimos movimientos del equipo</span></div><span class="status-badge status-badge--active">En línea</span></div>
        <div class="admin-card__body activity-list">
            <?php if ($recentActivity === []): ?><p class="text-secondary mb-0">Todavía no hay actividad registrada.</p><?php endif; ?>
            <?php foreach ($recentActivity as $activity): ?>
                <article class="activity-item">
                    <span class="activity-item__dot"></span>
                    <div><strong><?= e($activity['user_name'] ?? 'Sistema') ?></strong><p><?= e($activity['description']) ?></p></div>
                    <time><?= e(format_admin_date($activity['created_at'])) ?></time>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="admin-card dashboard-security">
        <div class="admin-card__header"><div><h2>Equipo y seguridad</h2><span class="admin-card__subtitle">Estado del acceso administrativo</span></div></div>
        <div class="dashboard-security__metrics">
            <div><span>Usuarios activos</span><strong><?= $securityStats['active_users'] ?></strong></div>
            <div><span>Ingresos hoy</span><strong><?= $securityStats['logins_today'] ?></strong></div>
            <div class="<?= $securityStats['failed_logins'] > 0 ? 'has-alert' : '' ?>"><span>Intentos rechazados</span><strong><?= $securityStats['failed_logins'] ?></strong></div>
        </div>
        <?php if (can('users.view')): ?><a class="dashboard-security__link" href="<?= e(admin_url('usuarios/')) ?>">Administrar usuarios y accesos →</a><?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
