<?php
// jwt.php - Simple JWT implementation (HMAC SHA256)
// Note: For production, use a secure library like firebase/php-jwt. This is a basic example.

$secret_key = "your_secret_key"; // Change this to a strong secret

function generate_jwt($payload) {
    global $secret_key;
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $base64_header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64_payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    $signature = hash_hmac('sha256', $base64_header . "." . $base64_payload, $secret_key, true);
    $base64_signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    return $base64_header . "." . $base64_payload . "." . $base64_signature;
}

function verify_jwt($jwt) {
    global $secret_key;
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    $signature = str_replace(['-', '_'], ['+', '/'], $parts[2]);
    $signature = base64_decode($signature);
    $expected_signature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $secret_key, true);
    if (!hash_equals($signature, $expected_signature)) return false;
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
    if (isset($payload['exp']) && $payload['exp'] < time()) return false;
    return $payload;
}
?>