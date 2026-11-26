<?php
/**
 * Masters PHP Core — DataTables JSON (client-side load)
 * DB: shared getDBConnection(), closed in jsonResponse.
 */
require_once __DIR__ . '/bootstrap.php';

jsonResponse(masterAjaxListJson($master));
