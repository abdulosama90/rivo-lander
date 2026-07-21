<?php
header('Content-Type: application/json');
echo json_encode([
  'php'      => PHP_VERSION,
  'curl'     => function_exists('curl_init'),
  'openssl'  => extension_loaded('openssl'),
  'json'     => extension_loaded('json'),
  'session'  => function_exists('session_start'),
  'mail'     => function_exists('mail'),
  'docroot'  => $_SERVER['DOCUMENT_ROOT'] ?? null,
  'above'    => is_readable(dirname($_SERVER['DOCUMENT_ROOT'] ?? '/')),
  'https'    => !empty($_SERVER['HTTPS']),
]);
