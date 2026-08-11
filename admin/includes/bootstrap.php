<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/config/database/connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('gocreative_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => site_path('/admin/'),
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://www.gstatic.com; style-src 'self' 'unsafe-inline'; script-src 'self' https://www.google.com https://www.gstatic.com; connect-src 'self' https://www.google.com https://www.gstatic.com; frame-src https://www.google.com https://recaptcha.google.com; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
header('Cache-Control: no-store, max-age=0');

function admin_url(string $path = ''): string
{
    return site_path('/admin/' . ltrim($path, '/'));
}

function redirect_admin(string $path = ''): void
{
    header('Location: ' . admin_url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['admin_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('La sesión del formulario expiró. Vuelve atrás, recarga la página e inténtalo nuevamente.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['admin_flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['admin_flash'] ?? [];
    unset($_SESSION['admin_flash']);
    return is_array($messages) ? $messages : [];
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

function admin_user_agent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 255);
}

function current_user(bool $refresh = false): ?array
{
    static $loaded = false;
    static $user = null;

    if ($refresh) {
        $loaded = false;
        $user = null;
    }

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    $userId = filter_var($_SESSION['admin_user_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$userId) {
        return null;
    }

    $statement = db()->prepare(
        'SELECT u.id, u.name, u.email, u.status, u.must_change_password, u.last_login_at,
                u.role_id, r.name AS role_name, r.slug AS role_slug
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $userId]);
    $result = $statement->fetch();

    if (!$result || $result['status'] !== 'active') {
        unset($_SESSION['admin_user_id']);
        return null;
    }

    $user = $result;
    return $user;
}

function require_auth(): array
{
    $user = current_user();
    if ($user === null) {
        flash('warning', 'Inicia sesión para continuar.');
        redirect_admin('login.php');
    }

    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ((int) $user['must_change_password'] === 1 && !in_array($script, ['cambiar-clave.php', 'logout.php'], true)) {
        flash('warning', 'Por seguridad debes crear una nueva contraseña antes de continuar.');
        redirect_admin('cambiar-clave.php');
    }

    return $user;
}

function user_permissions(?array $user = null): array
{
    static $permissions = null;

    if ($permissions !== null) {
        return $permissions;
    }

    $user ??= current_user();
    if ($user === null) {
        return $permissions = [];
    }

    if ($user['role_slug'] === 'superadministrador') {
        $statement = db()->query('SELECT slug FROM permissions ORDER BY slug');
    } else {
        $statement = db()->prepare(
            'SELECT p.slug
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = :role_id
             ORDER BY p.slug'
        );
        $statement->execute(['role_id' => $user['role_id']]);
    }

    return $permissions = array_column($statement->fetchAll(), 'slug');
}

function can(string $permission): bool
{
    return in_array($permission, user_permissions(), true);
}

function require_permission(string $permission): array
{
    $user = require_auth();
    if (!can($permission)) {
        http_response_code(403);
        $pageTitle = 'Acceso restringido';
        $activeMenu = '';
        require __DIR__ . '/header.php';
        echo '<div class="admin-empty"><span class="admin-empty__code">403</span><h1>No tienes permiso para esta acción</h1><p>Solicita acceso a un superadministrador.</p><a class="btn btn-dark" href="' . e(admin_url()) . '">Volver al panel</a></div>';
        require __DIR__ . '/footer.php';
        exit;
    }

    return $user;
}

function audit_log(string $action, string $entityType, ?int $entityId, string $description): void
{
    try {
        $user = current_user();
        $statement = db()->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent)
             VALUES (:user_id, :action, :entity_type, :entity_id, :description, :ip_address, :user_agent)'
        );
        $statement->execute([
            'user_id' => $user['id'] ?? null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => client_ip(),
            'user_agent' => admin_user_agent(),
        ]);
    } catch (Throwable $exception) {
        error_log('No se pudo registrar la auditoría: ' . $exception->getMessage());
    }
}

function login_is_blocked(string $email): bool
{
    $statement = db()->prepare(
        'SELECT COUNT(*)
         FROM login_attempts
         WHERE successful = 0
           AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
           AND (email = :email OR ip_address = :ip_address)'
    );
    $statement->execute(['email' => $email, 'ip_address' => client_ip()]);
    return (int) $statement->fetchColumn() >= 5;
}

function record_login_attempt(string $email, bool $successful): void
{
    $statement = db()->prepare(
        'INSERT INTO login_attempts (email, ip_address, successful) VALUES (:email, :ip_address, :successful)'
    );
    $statement->execute([
        'email' => $email,
        'ip_address' => client_ip(),
        'successful' => $successful ? 1 : 0,
    ]);
}

function attempt_login(string $email, string $password): array
{
    $email = mb_strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        return ['ok' => false, 'message' => 'Ingresa un correo y una contraseña válidos.'];
    }

    if (login_is_blocked($email)) {
        return ['ok' => false, 'message' => 'Demasiados intentos. Espera 15 minutos antes de volver a probar.'];
    }

    $statement = db()->prepare(
        'SELECT u.id, u.password_hash, u.status
         FROM users u
         WHERE u.email = :email
         LIMIT 1'
    );
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    $valid = $user && $user['status'] === 'active' && password_verify($password, $user['password_hash']);
    record_login_attempt($email, (bool) $valid);

    if (!$valid) {
        return ['ok' => false, 'message' => 'Correo o contraseña incorrectos.'];
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $rehash = db()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $rehash->execute(['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id']]);
    }

    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = (int) $user['id'];
    unset($_SESSION['admin_csrf']);

    $update = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $update->execute(['id' => $user['id']]);
    current_user(true);
    audit_log('login', 'session', (int) $user['id'], 'Inicio de sesión correcto');

    return ['ok' => true, 'message' => ''];
}

function validate_password_strength(string $password): array
{
    $errors = [];
    if (strlen($password) < 12) {
        $errors[] = 'La contraseña debe tener al menos 12 caracteres.';
    }
    if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Incluye letras mayúsculas y minúsculas.';
    }
    if (!preg_match('/\d/', $password) || !preg_match('/[^a-zA-Z\d]/', $password)) {
        $errors[] = 'Incluye al menos un número y un símbolo.';
    }
    return $errors;
}

function role_slug(string $name): string
{
    $value = trim(mb_strtolower($name));
    if (function_exists('iconv')) {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }
    }
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function is_last_active_superadmin(int $userId): bool
{
    $statement = db()->prepare(
        "SELECT COUNT(*)
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE r.slug = 'superadministrador' AND u.status = 'active' AND u.id <> :id"
    );
    $statement->execute(['id' => $userId]);
    return (int) $statement->fetchColumn() === 0;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $letters !== '' ? $letters : 'GC';
}

function format_admin_date(?string $date, string $fallback = 'Sin registro'): string
{
    if (!$date) {
        return $fallback;
    }
    return date('d-m-Y H:i', strtotime($date));
}
