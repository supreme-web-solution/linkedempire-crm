<?php

/**
 * Smoke-test extension v2 API endpoints against the local linkdominator app.
 * Usage: php scripts/v2-extension-api-smoke.php [email] [password]
 */

$base = rtrim(getenv('APP_URL') ?: 'http://127.0.0.1:8000', '/');
$email = $argv[1] ?? getenv('LD_TEST_EMAIL') ?: 'vicken408@gmail.com';
$password = $argv[2] ?? getenv('LD_TEST_PASSWORD') ?: '';

if ($password === '') {
    fwrite(STDERR, "Usage: php scripts/v2-extension-api-smoke.php email password\n");
    exit(1);
}

function req(string $method, string $url, ?array $body = null, ?string $token = null): array
{
    $ch = curl_init($url);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer '.$token;
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode((string) $raw, true);

    return ['status' => $status, 'body' => $json ?? $raw];
}

function line(string $feature, string $endpoint, array $res): void
{
    $ok = $res['status'] >= 200 && $res['status'] < 500;
    $flag = $ok ? 'OK' : 'FAIL';
    $msg = is_array($res['body']) ? ($res['body']['message'] ?? '') : substr((string) $res['body'], 0, 120);
    echo sprintf("[%s] %-3d %-28s %-40s %s\n", $flag, $res['status'], $feature, $endpoint, $msg);
}

echo "Base: {$base}\n\n";

$auth = req('POST', "{$base}/api/v2/auth/extension-token", [
    'email' => $email,
    'password' => $password,
]);
line('Auth', 'POST /auth/extension-token', $auth);
if ($auth['status'] !== 200) {
    exit(1);
}
$token = $auth['body']['token'] ?? $auth['body']['data']['token'] ?? null;
if (! $token) {
    echo "No token in auth response\n";
    exit(1);
}

$tests = [
    ['Access', 'GET', '/api/v2/access-check', null],
    ['Integration', 'GET', '/api/v2/integration-accounts', null],
    ['Audiences', 'GET', '/api/v2/leads/sn/sources', null],
    ['Audiences', 'GET', '/api/v2/leads/sn/imported', null],
    ['Campaign', 'GET', '/api/v2/campaigns', null],
    ['Accept Invites', 'GET', '/api/v2/outreach/invitations', null],
    ['Withdraw Invites', 'GET', '/api/v2/outreach/invitations/sent?limit=10', null],
    ['Msg All', 'GET', '/api/v2/outreach/relations?limit=10', null],
    ['Search params', 'GET', '/api/v2/outreach/search-parameters?type=LOCATION&keywords=US', null],
    ['ESP', 'GET', '/api/v2/esp/config', null],
    ['Conversations', 'GET', '/api/v2/conversations', null],
];

foreach ($tests as [$feature, $method, $path, $body]) {
    line($feature, "{$method} {$path}", req($method, $base.$path, $body, $token));
}

echo "\nDone. 404 = route missing. 422/401/403 = auth/LinkedIn/config issue (route exists).\n";
