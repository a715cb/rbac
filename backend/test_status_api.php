<?php
require __DIR__ . '/vendor/autoload.php';

$app = new \think\App();
$app->initialize();

// 直接通过 HTTP 客户端测试
$httpClient = new \GuzzleHttp\Client();

// 先登录获取 token
$loginResp = $httpClient->post('http://localhost:8000/admin/login', [
    'json' => ['username' => 'admin', 'password' => '123456']
]);
$loginData = json_decode($loginResp->getBody()->getContents(), true);
echo "Login: code={$loginData['code']}\n";

if ($loginData['code'] !== 200) {
    echo "Login failed: {$loginData['msg']}\n";
    exit(1);
}

$token = $loginData['data']['access_token'];

// 测试 PUT /admin/roles/1/status
echo "\n--- Testing PUT /admin/roles/1/status ---\n";
try {
    $resp = $httpClient->put('http://localhost:8000/admin/roles/1/status', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ],
        'json' => ['status' => 0]
    ]);
    $data = json_decode($resp->getBody()->getContents(), true);
    echo "Response: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
} catch (\GuzzleHttp\Exception\ClientException $e) {
    $resp = $e->getResponse();
    $body = $resp->getBody()->getContents();
    echo "Error response: {$body}\n";
    echo "Status code: {$resp->getStatusCode()}\n";
}

// 测试 PUT /admin/roles/1/change-status (旧路径)
echo "\n--- Testing PUT /admin/roles/1/change-status ---\n";
try {
    $resp = $httpClient->put('http://localhost:8000/admin/roles/1/change-status', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ],
        'json' => ['status' => 0]
    ]);
    $data = json_decode($resp->getBody()->getContents(), true);
    echo "Response: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
} catch (\GuzzleHttp\Exception\ClientException $e) {
    $resp = $e->getResponse();
    $body = $resp->getBody()->getContents();
    echo "Error response: {$body}\n";
    echo "Status code: {$resp->getStatusCode()}\n";
}

// 恢复状态
echo "\n--- Restoring status ---\n";
try {
    $resp = $httpClient->put('http://localhost:8000/admin/roles/1/status', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ],
        'json' => ['status' => 1]
    ]);
    $data = json_decode($resp->getBody()->getContents(), true);
    echo "Restore response: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
} catch (\GuzzleHttp\Exception\ClientException $e) {
    $resp = $e->getResponse();
    $body = $resp->getBody()->getContents();
    echo "Restore error: {$body}\n";
}
