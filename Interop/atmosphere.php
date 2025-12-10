<?php
// Proxy pour webetu
$opts = array(
    'http' => array(
        'proxy'=> 'tcp://127.0.0.1:8080',
        'request_fulluri'=> true
    ),
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false
    )
);
$context = stream_context_create($opts);
stream_context_set_default($opts);

// Petit helper pour récupérer une URL
function get_url($url) {
    global $context;
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        return null;
    }
    return $data;
}

// IP client (derrière proxy)
function get_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // X-Forwarded-For: IP1, IP2,... → on prend la première
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}
?>
