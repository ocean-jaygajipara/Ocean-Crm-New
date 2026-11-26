<?php
/**
 * API — Logout (revoke token)
 * POST/GET: token (or Authorization: Bearer <token>)
 */
require_once __DIR__ . '/_bootstrap.php';

$token = apiGetBearerToken();
if ($token === '') {
    apiJson(false, 'token is required.', [
        'required' => ['token'],
        'headers'  => ['Authorization: Bearer <token>', 'X-Api-Token: <token>'],
    ], 422);
}

$ok = apiRevokeToken($token);
if (!$ok) {
    apiJson(false, 'Token not found or already logged out.', null, 404);
}

apiJson(true, 'Logout successful.', null);
