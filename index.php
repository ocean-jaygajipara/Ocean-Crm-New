<?php
/**
 * Login Page — Single company CRM login
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/permission_helper.php';

$companyName = getCompanyName();
$companyLogo = getLoginLogo();
if ($companyLogo === '' || !is_file(__DIR__ . '/' . ltrim((string) $companyLogo, '/'))) {
    $companyLogo = 'assets/images/ocean-crm-logo.png';
}
$hasLogoFile = is_file(__DIR__ . '/' . ltrim((string) $companyLogo, '/'));

if (isLoggedIn()) {
    header('Location: ' . app_url('dashboard.php'));
    exit;
}

$error = '';
$dbRoles = ['admin', 'hr', 'employee'];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameInput = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (function_exists('mb_strtoupper')) {
        $usernameKey = mb_strtoupper($usernameInput, 'UTF-8');
    } else {
        $usernameKey = strtoupper($usernameInput);
    }

    if ($usernameInput === '' || $password === '') {
        $error = 'Please enter username and password.';
        $username = $usernameInput;
    } else {
        ensureRoleTables();
        $conn = getDBConnection();
        $stmt = $conn->prepare(
            "SELECT id, username, password, full_name, role, department_id, custom_role_id, employee_id, status
             FROM users WHERE username = ? AND status = 1 LIMIT 1"
        );
        $stmt->bind_param('s', $usernameKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        $roleName = '';
        if ($user && !empty($user['custom_role_id'])) {
            $rs = $conn->prepare('SELECT name FROM roles WHERE id = ? AND status = 1 LIMIT 1');
            $rid = (int) $user['custom_role_id'];
            $rs->bind_param('i', $rid);
            $rs->execute();
            $rr = $rs->get_result()->fetch_assoc();
            $rs->close();
            $roleName = (string) ($rr['name'] ?? '');
        }

        $userRole = strtolower((string) ($user['role'] ?? ''));

        if (!$user || $user['password'] !== $password) {
            $error = 'Invalid username or password.';
            $username = $usernameInput;
        } elseif (!in_array($userRole, $dbRoles, true)) {
            $error = 'This account cannot sign in here.';
            $username = $usernameInput;
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['department_id'] = $user['department_id'];
            $_SESSION['employee_id'] = !empty($user['employee_id']) ? (int) $user['employee_id'] : 0;
            $_SESSION['custom_role_id'] = !empty($user['custom_role_id']) ? (int) $user['custom_role_id'] : 0;
            $_SESSION['custom_role_name'] = $roleName;
            unset($_SESSION['permissions'], $_SESSION['permissions_role_id']);
            if ($_SESSION['custom_role_id'] > 0) {
                loadUserPermissionsIntoSession($_SESSION['custom_role_id'], true);
            } else {
                $_SESSION['permissions'] = [];
                $_SESSION['permissions_role_id'] = 0;
            }
            header('Location: ' . app_url('dashboard.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($companyName); ?></title>
    <?php $favVer = (int) @filemtime(__DIR__ . '/assets/images/fav.png'); ?>
    <link rel="icon" type="image/png" href="<?php echo app_url('assets/images/fav.png'); ?>?v=<?php echo $favVer; ?>">
    <link rel="shortcut icon" type="image/png" href="<?php echo app_url('assets/images/fav.png'); ?>?v=<?php echo $favVer; ?>">
    <link rel="apple-touch-icon" href="<?php echo app_url('assets/images/fav.png'); ?>?v=<?php echo $favVer; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css?v=<?php echo filemtime(__DIR__ . '/assets/css/login.css'); ?>">
</head>
<body class="login-page">

<div class="login-bg" aria-hidden="true">
    <div class="login-bg-glow login-bg-glow-a"></div>
    <div class="login-bg-glow login-bg-glow-b"></div>
    <div class="login-bg-glow login-bg-glow-c"></div>
    <div class="login-bg-grid"></div>
    <div class="login-bg-sweep"></div>

    <!-- IT company watermarks -->
    <!-- Network / cloud nodes -->
    <svg class="bio-wm bio-wm-1" viewBox="0 0 120 120" aria-hidden="true">
        <circle cx="28" cy="30" r="9" fill="#2466FB"/>
        <circle cx="92" cy="28" r="9" fill="#1A4FD6"/>
        <circle cx="60" cy="70" r="10" fill="#0F172A"/>
        <circle cx="30" cy="95" r="7" fill="#4B8BFF"/>
        <circle cx="95" cy="88" r="7" fill="#2466FB"/>
        <line x1="28" y1="30" x2="92" y2="28" stroke="#2466FB" stroke-width="3" opacity="0.7"/>
        <line x1="28" y1="30" x2="60" y2="70" stroke="#1A4FD6" stroke-width="3" opacity="0.7"/>
        <line x1="92" y1="28" x2="60" y2="70" stroke="#2466FB" stroke-width="3" opacity="0.7"/>
        <line x1="60" y1="70" x2="30" y2="95" stroke="#4B8BFF" stroke-width="3" opacity="0.7"/>
        <line x1="60" y1="70" x2="95" y2="88" stroke="#2466FB" stroke-width="3" opacity="0.7"/>
    </svg>
    <!-- Laptop -->
    <svg class="bio-wm bio-wm-2" viewBox="0 0 96 72" aria-hidden="true">
        <rect x="14" y="8" width="68" height="44" rx="4" fill="none" stroke="#2466FB" stroke-width="3"/>
        <rect x="22" y="16" width="52" height="28" rx="2" fill="#2466FB" opacity="0.2"/>
        <path d="M8 56 h80 l-6 8 H14 Z" fill="#2466FB" opacity="0.55"/>
        <rect x="42" y="48" width="12" height="4" rx="1" fill="#1A4FD6"/>
    </svg>
    <!-- Code brackets -->
    <svg class="bio-wm bio-wm-3" viewBox="0 0 64 64" aria-hidden="true">
        <path d="M22 12 L8 32 L22 52" fill="none" stroke="#2466FB" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M42 12 L56 32 L42 52" fill="none" stroke="#1A4FD6" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M36 14 L28 50" fill="none" stroke="#0F172A" stroke-width="4" stroke-linecap="round" opacity="0.45"/>
    </svg>

</div>

<div class="login-center">
    <div class="login-card anim-in">
        <header class="login-brand">
            <?php if ($hasLogoFile): ?>
                <div class="login-logo-wrap anim-logo">
                    <img src="<?php echo htmlspecialchars($companyLogo); ?>?v=<?php echo @filemtime(__DIR__ . '/' . $companyLogo) ?: time(); ?>"
                         alt="<?php echo htmlspecialchars($companyName); ?>"
                         class="login-logo login-logo-custom login-logo-ocean">
                </div>
            <?php else: ?>
                <div class="login-wordmark anim-wordmark">
                    <span class="wm-brand">OCEAN</span>
                    <span class="wm-fire">CRM</span>
                </div>
            <?php endif; ?>

            <span class="login-hub-badge">Secure Login</span>
            <h1 class="login-portal-title">Customer Relationship Management</h1>
            <p class="login-tagline">Ocean CRM</p>
        </header>

        <?php if ($error !== ''): ?>
            <div class="login-alert anim-shake" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" class="login-form" autocomplete="off">
            <div class="login-field">
                <label for="username">Username</label>
                <div class="login-input-wrap">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Enter username" required
                           autocomplete="username"
                           value="<?php echo htmlspecialchars($username); ?>">
                </div>
            </div>

            <div class="login-field">
                <label for="password">Password</label>
                <div class="login-input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter password" required
                           autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="login-btn" id="btnLogin">
                <span class="login-btn-text">
                    Sign In
                    <i class="fa-solid fa-arrow-right"></i>
                </span>
                <span class="login-btn-load" hidden>
                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                    Authenticating...
                </span>
            </button>
        </form>

        <a href="<?php echo htmlspecialchars(app_url('register.php')); ?>" class="login-btn login-btn-secondary">
            <span class="login-btn-text">
                Registration
                <i class="fa-solid fa-user-plus"></i>
            </span>
        </a>

        <footer class="login-card-foot">
            <span class="foot-label">Designed &amp; Developed by</span>
            <a class="foot-brand" href="https://www.oceaninfotech.co.in/" target="_blank" rel="noopener">Ocean Infotech</a>
        </footer>
    </div>

    <p class="login-copy anim-in" style="--delay:.3s">
        © <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyName); ?> · Ocean CRM
    </p>
</div>

<script src="assets/js/login.js"></script>
</body>
</html>
