<?php
/**
 * Company Registration — save company basic details + create Admin account
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/settings.php';

$brandName = 'Ocean CRM';
$companyLogo = getLoginLogo();
if ($companyLogo === '' || !is_file(__DIR__ . '/' . ltrim((string) $companyLogo, '/'))) {
    $companyLogo = 'assets/images/ocean-crm-logo.png';
}
$hasLogoFile = is_file(__DIR__ . '/' . ltrim((string) $companyLogo, '/'));

if (isLoggedIn()) {
    header('Location: ' . app_url('index.php'));
    exit;
}

function ensureUsersTableForRegister()
{
    require_once __DIR__ . '/includes/dbsync.php';
    syncDatabase(getDBConnection(), false);
}

$error = '';
$success = '';
$form = [
    'company_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'pincode' => '',
    'gstin' => '',
    'website' => '',
    'admin_name' => '',
    'username' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $_) {
        $form[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (function_exists('mb_strtoupper')) {
        $usernameKey = mb_strtoupper($form['username'], 'UTF-8');
    } else {
        $usernameKey = strtoupper($form['username']);
    }

    if (
        $form['company_name'] === '' || $form['email'] === '' || $form['phone'] === ''
        || $form['admin_name'] === '' || $form['username'] === ''
        || $password === '' || $confirm === ''
    ) {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid company email.';
    } elseif (strlen($form['username']) < 3) {
        $error = 'Admin username must be at least 3 characters.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Password and confirm password do not match.';
    } else {
        try {
            ensureUsersTableForRegister();
            $conn = getDBConnection();
            ensureCompanySettingsSchema($conn);

            $check = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $check->bind_param('s', $usernameKey);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();

            if ($exists) {
                $error = 'This admin username is already taken. Please choose another.';
            } else {
                $headerLines = array_filter([
                    $form['company_name'],
                    $form['address'],
                    trim($form['city'] . ($form['state'] !== '' ? ', ' . $form['state'] : '') . ($form['pincode'] !== '' ? ' - ' . $form['pincode'] : '')),
                    $form['phone'] !== '' ? ('Phone: ' . $form['phone']) : '',
                    $form['email'] !== '' ? ('Email: ' . $form['email']) : '',
                    $form['gstin'] !== '' ? ('GSTIN: ' . $form['gstin']) : '',
                    $form['website'] !== '' ? ('Web: ' . $form['website']) : '',
                ], static function ($v) {
                    return $v !== null && trim((string) $v) !== '';
                });
                $headerDetails = implode("\n", $headerLines);
                $footerDetails = $form['company_name'] . ' · Powered by Ocean CRM';
                $defaultLogo = 'assets/images/ocean-crm-logo.png';

                $conn->begin_transaction();
                try {
                    $upd = $conn->prepare(
                        "UPDATE company_settings
                         SET company_name = ?,
                             email = ?,
                             phone = ?,
                             address = ?,
                             city = ?,
                             state = ?,
                             pincode = ?,
                             gstin = ?,
                             website = ?,
                             header_details = ?,
                             footer_details = ?,
                             company_logo = COALESCE(NULLIF(company_logo, ''), ?),
                             login_logo = COALESCE(NULLIF(login_logo, ''), ?),
                             dashboard_logo = COALESCE(NULLIF(dashboard_logo, ''), ?)
                         WHERE id = 1"
                    );
                    $upd->bind_param(
                        'ssssssssssssss',
                        $form['company_name'],
                        $form['email'],
                        $form['phone'],
                        $form['address'],
                        $form['city'],
                        $form['state'],
                        $form['pincode'],
                        $form['gstin'],
                        $form['website'],
                        $headerDetails,
                        $footerDetails,
                        $defaultLogo,
                        $defaultLogo,
                        $defaultLogo
                    );
                    if (!$upd->execute()) {
                        throw new RuntimeException('Could not save company details.');
                    }
                    $upd->close();

                    $role = 'admin';
                    $status = 1;
                    $ins = $conn->prepare(
                        'INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, ?, ?, ?)'
                    );
                    $ins->bind_param('ssssi', $usernameKey, $password, $form['admin_name'], $role, $status);
                    if (!$ins->execute()) {
                        throw new RuntimeException('Could not create admin account.');
                    }
                    $ins->close();

                    $conn->commit();
                    $success = 'Company registered successfully. Admin account created. Please Sign In with Admin Login.';
                    $form = array_map(static function () {
                        return '';
                    }, $form);
                } catch (Throwable $inner) {
                    $conn->rollback();
                    $error = $inner->getMessage();
                }
            }
        } catch (Throwable $e) {
            $error = 'Registration failed. Please check database connection.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Registration - <?php echo htmlspecialchars($brandName); ?></title>
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
</div>

<div class="login-center">
    <div class="login-card login-card-wide anim-in">
        <header class="login-brand">
            <?php if ($hasLogoFile): ?>
                <div class="login-logo-wrap anim-logo">
                    <img src="<?php echo htmlspecialchars($companyLogo); ?>?v=<?php echo @filemtime(__DIR__ . '/' . $companyLogo) ?: time(); ?>"
                         alt="<?php echo htmlspecialchars($brandName); ?>"
                         class="login-logo login-logo-custom login-logo-ocean">
                </div>
            <?php else: ?>
                <div class="login-wordmark anim-wordmark">
                    <span class="wm-brand">OCEAN</span>
                    <span class="wm-fire">CRM</span>
                </div>
            <?php endif; ?>

            <span class="login-hub-badge">Company Setup</span>
            <h1 class="login-portal-title">Company Registration</h1>
            <p class="login-tagline">Register company · Create admin</p>
        </header>

        <?php if ($error !== ''): ?>
            <div class="login-alert anim-shake" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="login-alert login-alert-success" role="status">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <a href="<?php echo htmlspecialchars(app_url('index.php')); ?>" class="login-btn" style="margin-top:12px;text-decoration:none;display:flex;justify-content:center;">
                <span class="login-btn-text">Go to Sign In <i class="fa-solid fa-arrow-right"></i></span>
            </a>
        <?php else: ?>
        <form method="POST" action="" class="login-form" autocomplete="off">
            <p class="reg-section-title"><i class="fa-solid fa-building"></i> Company Basic Details</p>

            <div class="login-field">
                <label for="company_name">Company Name <em>*</em></label>
                <div class="login-input-wrap">
                    <i class="fa-solid fa-building"></i>
                    <input type="text" id="company_name" name="company_name" placeholder="Your company name" required
                           value="<?php echo htmlspecialchars($form['company_name']); ?>">
                </div>
            </div>

            <div class="reg-grid">
                <div class="login-field">
                    <label for="email">Company Email <em>*</em></label>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="company@email.com" required
                               value="<?php echo htmlspecialchars($form['email']); ?>">
                    </div>
                </div>
                <div class="login-field">
                    <label for="phone">Phone <em>*</em></label>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-phone"></i>
                        <input type="text" id="phone" name="phone" placeholder="Mobile / landline" required
                               value="<?php echo htmlspecialchars($form['phone']); ?>">
                    </div>
                </div>
            </div>

            <div class="login-field">
                <label for="address">Address</label>
                <div class="login-input-wrap">
                    <i class="fa-solid fa-location-dot"></i>
                    <input type="text" id="address" name="address" placeholder="Street / area"
                           value="<?php echo htmlspecialchars($form['address']); ?>">
                </div>
            </div>

            <div class="reg-grid reg-grid-3">
                <div class="login-field">
                    <label for="city">City</label>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-city"></i>
                        <input type="text" id="city" name="city" placeholder="City"
                               value="<?php echo htmlspecialchars($form['city']); ?>">
                    </div>
                </div>
                <div class="login-field">
                    <label for="state">State</label>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-map"></i>
                        <input type="text" id="state" name="state" placeholder="State"
                               value="<?php echo htmlspecialchars($form['state']); ?>">
                    </div>
                </div>
                <div class="login-field">
                    <label for="pincode">Pincode</label>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-hashtag"></i>
                        <input type="text" id="pincode" name="pincode" placeholder="Pincode"
                               value="<?php echo htmlspecialchars($form['pincode']); ?>">
                    </div>
                </div>
            </div>

            <div class="reg-grid">
                <div class="login-field">
                    <label for="gstin">GSTIN</label>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-receipt"></i>
                        <input type="text" id="gstin" name="gstin" placeholder="Optional GST number"
                               value="<?php echo htmlspecialchars($form['gstin']); ?>">
                    </div>
                </div>
                <div class="login-field">
                    <label for="website">Website</label>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-globe"></i>
                        <input type="text" id="website" name="website" placeholder="www.example.com"
                               value="<?php echo htmlspecialchars($form['website']); ?>">
                    </div>
                </div>
            </div>

            <p class="reg-section-title"><i class="fa-solid fa-user-shield"></i> Admin Account</p>

            <div class="login-field">
                <label for="admin_name">Admin Full Name <em>*</em></label>
                <div class="login-input-wrap">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" id="admin_name" name="admin_name" placeholder="Admin full name" required
                           value="<?php echo htmlspecialchars($form['admin_name']); ?>">
                </div>
            </div>

            <div class="login-field">
                <label for="username">Admin Username <em>*</em></label>
                <div class="login-input-wrap">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Choose admin username" required
                           autocomplete="username"
                           value="<?php echo htmlspecialchars($form['username']); ?>">
                </div>
            </div>

            <div class="reg-grid">
                <div class="login-field">
                    <label for="password">Password <em>*</em></label>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Create password" required
                               autocomplete="new-password">
                    </div>
                </div>
                <div class="login-field">
                    <label for="confirm_password">Confirm Password <em>*</em></label>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required
                               autocomplete="new-password">
                    </div>
                </div>
            </div>

            <button type="submit" class="login-btn">
                <span class="login-btn-text">
                    Register Company
                    <i class="fa-solid fa-building-circle-check"></i>
                </span>
            </button>
        </form>
        <?php endif; ?>

        <div class="login-switch">
            <span>Already registered?</span>
            <a href="<?php echo htmlspecialchars(app_url('index.php')); ?>">Sign In</a>
        </div>

        <footer class="login-card-foot">
            <span class="foot-label">Designed &amp; Developed by</span>
            <a class="foot-brand" href="https://www.oceaninfotech.co.in/" target="_blank" rel="noopener">Ocean Infotech</a>
        </footer>
    </div>
</div>

</body>
</html>
