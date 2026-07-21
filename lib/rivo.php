<?php
/**
 * Rivo — shared helpers.
 * Secrets are NEVER stored here. They live in rivo-config.php, one level above
 * public_html, which is outside the web root and outside this git repository.
 */

function rivo_config() {
    static $cfg = null;
    if ($cfg !== null) return $cfg;

    // DOCUMENT_ROOT is .../public_html/rivo  ->  account root is two levels up.
    $candidates = [
        dirname(dirname(rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'))) . '/rivo-config.php',
        dirname(dirname(__DIR__)) . '/rivo-config.php',
    ];
    foreach ($candidates as $path) {
        if (is_readable($path)) { $cfg = require $path; return $cfg; }
    }
    $cfg = [];
    return $cfg;
}

function rivo_cfg($key, $default = null) {
    $c = rivo_config();
    return isset($c[$key]) && $c[$key] !== '' ? $c[$key] : $default;
}

/** Bearer header value in the format the 20i API expects. */
function rivo_bearer($key) {
    return 'Bearer ' . base64_encode($key);
}

/**
 * Minimal JSON HTTP call. Returns [status, decoded_body].
 * Never logs request bodies — they can contain credentials.
 */
function rivo_http($method, $url, $key, $payload = null, $timeout = 25) {
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json', 'Authorization: ' . rivo_bearer($key), 'Expect:'];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'RivoLander/1.0',
        CURLOPT_CUSTOMREQUEST  => $method,
    ];
    if ($payload !== null) $opts[CURLOPT_POSTFIELDS] = json_encode($payload);
    curl_setopt_array($ch, $opts);
    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$status, $body === false ? null : json_decode($body, true)];
}

/** Verify a customer's credentials. Returns an interactive access token or null. */
function rivo_authenticate($username, $password) {
    $client = rivo_cfg('oauth_client_key');
    if (!$client) return null;
    list($status, $body) = rivo_http(
        'POST',
        'https://auth-api.20i.com:3000/login/authenticate',
        $client,
        ['grant_type' => 'password', 'username' => $username, 'password' => $password]
    );
    if ($status === 200 && !empty($body['access_token'])) return $body;
    return null;
}

/** Turn an interactive token into a control-panel SSO URL at app.rivo.host. */
function rivo_sso_url($access_token) {
    $general = rivo_cfg('general_api_key');
    if (!$general) return null;

    list($s1, $cust) = rivo_http('GET', 'https://api.20i.com/reseller/*/customisations', $general);
    if ($s1 !== 200 || !is_array($cust)) return null;

    list($s2, $sess) = rivo_http('POST', 'https://www.stackcp.com/login/implicitBranded', $access_token, ['brand' => $cust]);
    if ($s2 !== 200 || empty($sess['session_id'])) return null;

    $host = 'www.stackcp.com';
    if (!empty($cust['brandDomain']) && $cust['brandDomain'] !== '*' && !empty($cust['brandSubdomain'])) {
        $host = $cust['brandSubdomain'] . '.' . $cust['brandDomain'];
    }
    return 'https://' . $host . '/login/sso?' . http_build_query(['PHPSESSID' => $sess['session_id']]);
}

/** Simple per-session throttle so the form can't be used to guess passwords. */
function rivo_throttle($max = 6, $window = 300) {
    if (!isset($_SESSION['rivo_attempts'])) $_SESSION['rivo_attempts'] = [];
    $now = time();
    $_SESSION['rivo_attempts'] = array_values(array_filter(
        $_SESSION['rivo_attempts'],
        function ($t) use ($now, $window) { return $t > $now - $window; }
    ));
    return count($_SESSION['rivo_attempts']) < $max;
}

function rivo_note_attempt() { $_SESSION['rivo_attempts'][] = time(); }
