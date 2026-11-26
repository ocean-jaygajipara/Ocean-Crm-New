<?php
/**
 * Notifications AJAX — list / mark read / mark all
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification_helper.php';

requireLogin();
ensureNotificationTables();

$action = strtolower(trim((string) ($_REQUEST['action'] ?? 'list')));
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($action === 'mark_read') {
    $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $ok = markNotificationRead($id, $userId);
    $pack = fetchUserNotifications($userId, 20);
    jsonResponse([
        'ok'     => $ok,
        'unread' => $pack['unread'],
        'items'  => $pack['items'],
    ]);
}

if ($action === 'mark_all') {
    $ok = markAllNotificationsRead($userId);
    $pack = fetchUserNotifications($userId, 20);
    jsonResponse([
        'ok'     => $ok,
        'unread' => $pack['unread'],
        'items'  => $pack['items'],
        'message'=> 'All notifications marked as read.',
    ]);
}

// default: list
$pack = fetchUserNotifications($userId, 20);
jsonResponse([
    'ok'     => true,
    'unread' => $pack['unread'],
    'items'  => $pack['items'],
]);
