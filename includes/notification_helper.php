<?php
/**
 * In-app notifications (header bell)
 */

function ensureNotificationTables($conn = null)
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;
    if ($conn === null) {
        $conn = getDBConnection();
    }
    if (!function_exists('syncDatabase')) {
        require_once __DIR__ . '/dbsync.php';
    }
    syncDatabase($conn, false);
}

/**
 * Create one notification for a user.
 *
 * @return int new id (0 on fail)
 */
function createNotification(array $data)
{
    ensureNotificationTables();
    $conn = getDBConnection();

    $userId = (int) ($data['user_id'] ?? 0);
    $title = trim((string) ($data['title'] ?? ''));
    if ($userId <= 0 || $title === '') {
        return 0;
    }

    $type = trim((string) ($data['type'] ?? 'general'));
    if ($type === '') {
        $type = 'general';
    }
    $message = trim((string) ($data['message'] ?? ''));
    $link = trim((string) ($data['link'] ?? ''));
    $refType = trim((string) ($data['ref_type'] ?? ''));
    $refId = (int) ($data['ref_id'] ?? 0);
    $createdBy = (int) ($data['created_by'] ?? 0);

    $stmt = $conn->prepare(
        'INSERT INTO notifications
         (user_id, type, title, message, link, ref_type, ref_id, is_read, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)'
    );
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param(
        'isssssii',
        $userId,
        $type,
        $title,
        $message,
        $link,
        $refType,
        $refId,
        $createdBy
    );
    $ok = $stmt->execute();
    $id = (int) $stmt->insert_id;
    $stmt->close();
    return $ok ? $id : 0;
}

/**
 * Active admin user ids.
 *
 * @return int[]
 */
function getAdminUserIds($conn = null)
{
    if ($conn === null) {
        $conn = getDBConnection();
    }
    $ids = [];
    $res = $conn->query(
        "SELECT id FROM users
         WHERE status = 1 AND (role_id IS NULL OR role_id = 0)
           AND (employee_id IS NULL OR employee_id = 0)
         ORDER BY id ASC"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }
    }
    return $ids;
}

/**
 * When any user adds an inquiry → notify all admins (except creator).
 */
function notifyAdminsNewInquiry($inquiryId, $inquiryNo, $customerName, $createdByUserId)
{
    $inquiryId = (int) $inquiryId;
    $createdByUserId = (int) $createdByUserId;
    if ($inquiryId <= 0) {
        return 0;
    }

    ensureNotificationTables();
    $conn = getDBConnection();

    $creatorName = '';
    if ($createdByUserId > 0) {
        $st = $conn->prepare('SELECT full_name, username FROM users WHERE id = ? LIMIT 1');
        $st->bind_param('i', $createdByUserId);
        $st->execute();
        $urow = $st->get_result()->fetch_assoc();
        $st->close();
        if ($urow) {
            $creatorName = trim((string) ($urow['full_name'] ?? ''));
            if ($creatorName === '') {
                $creatorName = trim((string) ($urow['username'] ?? ''));
            }
        }
    }
    if ($creatorName === '') {
        $creatorName = 'Someone';
    }

    $inquiryNo = trim((string) $inquiryNo);
    $customerName = trim((string) $customerName);
    $title = 'New inquiry added';
    $message = $creatorName . ' added inquiry'
        . ($inquiryNo !== '' ? ' ' . $inquiryNo : '')
        . ($customerName !== '' ? ' for ' . $customerName : '')
        . '.';

    if (!function_exists('app_url')) {
        require_once __DIR__ . '/../config/app.php';
    }
    $link = app_url('inquiry/edit.php?id=' . $inquiryId);

    $count = 0;
    foreach (getAdminUserIds($conn) as $adminId) {
        if ($adminId === $createdByUserId) {
            continue; // don't notify self
        }
        $nid = createNotification([
            'user_id'    => $adminId,
            'type'       => 'inquiry_added',
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'ref_type'   => 'inquiry',
            'ref_id'     => $inquiryId,
            'created_by' => $createdByUserId,
        ]);
        if ($nid > 0) {
            $count++;
        }
    }
    return $count;
}

/**
 * Notifications for current (or given) user.
 *
 * @return array{items:array,unread:int}
 */
function fetchUserNotifications($userId = null, $limit = 20)
{
    ensureNotificationTables();
    $userId = $userId !== null ? (int) $userId : (int) ($_SESSION['user_id'] ?? 0);
    $limit = max(1, min(50, (int) $limit));
    $out = ['items' => [], 'unread' => 0];
    if ($userId <= 0) {
        return $out;
    }

    $conn = getDBConnection();
    $cntStmt = $conn->prepare(
        'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0'
    );
    $cntStmt->bind_param('i', $userId);
    $cntStmt->execute();
    $out['unread'] = (int) ($cntStmt->get_result()->fetch_assoc()['c'] ?? 0);
    $cntStmt->close();

    $stmt = $conn->prepare(
        'SELECT id, type, title, message, link, ref_type, ref_id, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT ' . $limit
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $out['items'][] = [
            'id'         => (int) $row['id'],
            'type'       => (string) $row['type'],
            'title'      => (string) $row['title'],
            'message'    => (string) ($row['message'] ?? ''),
            'link'       => (string) ($row['link'] ?? ''),
            'ref_type'   => (string) ($row['ref_type'] ?? ''),
            'ref_id'     => (int) ($row['ref_id'] ?? 0),
            'is_read'    => (int) ($row['is_read'] ?? 0) === 1,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'time_ago'   => notificationTimeAgo((string) ($row['created_at'] ?? '')),
        ];
    }
    $stmt->close();
    return $out;
}

function notificationTimeAgo($datetime)
{
    $ts = strtotime($datetime);
    if ($ts <= 0) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return $m . ' min ago';
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return $h . ' hour' . ($h === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400 * 7) {
        $d = (int) floor($diff / 86400);
        return $d . ' day' . ($d === 1 ? '' : 's') . ' ago';
    }
    return date('d-m-Y H:i', $ts);
}

function markNotificationRead($notificationId, $userId = null)
{
    ensureNotificationTables();
    $notificationId = (int) $notificationId;
    $userId = $userId !== null ? (int) $userId : (int) ($_SESSION['user_id'] ?? 0);
    if ($notificationId <= 0 || $userId <= 0) {
        return false;
    }
    $conn = getDBConnection();
    $stmt = $conn->prepare(
        'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
    );
    $stmt->bind_param('ii', $notificationId, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool) $ok;
}

function markAllNotificationsRead($userId = null)
{
    ensureNotificationTables();
    $userId = $userId !== null ? (int) $userId : (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return false;
    }
    $conn = getDBConnection();
    $stmt = $conn->prepare(
        'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0'
    );
    $stmt->bind_param('i', $userId);
    $ok = $stmt->execute();
    $stmt->close();
    return (bool) $ok;
}
