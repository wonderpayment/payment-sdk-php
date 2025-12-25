<?php

// 测试配置文件
// 与 test_payment_sdk.php 保持一致的配置方式

// 从文件中读取私钥
$privateKeyFile = dirname(__DIR__) . '/private_key.md';

// 检查文件是否存在
if (!file_exists($privateKeyFile)) {
    echo "私钥文件不存在，请在项目根目录下创建 private_key.md 文件并添加您的私钥。\n";
    exit(1);
}

// 读取文件内容
$privateKeyContent = file_get_contents($privateKeyFile);

if ($privateKeyContent === false) {
    echo "无法读取私钥文件，请检查 private_key.md 文件权限。\n";
    exit(1);
}

// 验证内容是否为有效的私钥格式
$trimmedPrivateKey = trim($privateKeyContent);
if (strpos($trimmedPrivateKey, '-----BEGIN RSA PRIVATE KEY-----') !== 0 &&
    strpos($trimmedPrivateKey, '-----BEGIN PRIVATE KEY-----') !== 0) {
    echo "私钥文件格式不正确，请确保 private_key.md 文件中包含有效的RSA私钥。\n";
    exit(1);
}

// 固定配置参数（与 test_payment_sdk.php 保持一致）
return [
    'appid' => '9a1e9fc2-4626-496e-9136-c1574aa319c6', // 固定的应用ID
    'signaturePrivateKey' => $trimmedPrivateKey, // 从 private_key.md 文件读取的私钥
    'webhookVerifyPublicKey' => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwJwg82VdH+/r+f+0
w0rEkLmT6y9d5LkYxqkKlQvQ1q4gF5fCvzW+HmMf8C0kVQH9X5sZsJ7HhYUZ
w2pX3n8t6U9v4w1qE0cR5pL6oN3vJ7y2zX4w5a6b7c8d9e0f1g2h3i4j5k6
l7m8n9o0p1q2r3s4t5u6v7w8x9y0z1A2B3c4d5e6f7g8h9i0j1k2l3m4n5
o6p7q8r9s0t1u2v3w4x5y6z7A8B9C0d1e2f3g4h5i6j7k8l9m0n1o2p3q4
r5s6t7u8v9w0x1y2z3A4B5C6D7E8F9G0H1i2j3k4l5m6n7o8p9q0r1s2t3
u4v5w6x7y8z9A0B1C2D3E4F5G6H7I8J9K0l1m2n3o4p5q6r7s8t9u0v1w2
x3y4z5A6B7C8D9E0F1G2H3I4J5K6L7M8N9o0p1q2r3s4t5u6v7w8x9y0z1
A2B3C4D5E6F7G8H9I0J1K2L3M4N5O6P7Q8r9s0t1u2v3w4x5y6z7A8B9C0
D1E2F3G4H5I6J7K8L9M0N1O2P3Q4R5S6T7U8V9W0X1Y2Z3A4B5C6D7E8F
9G0H1I2J3K4L5M6N7O8P9Q0R1S2T3U4V5W6X7Y8Z9A0B1C2D3E4F5G6H7
I8J9K0L1M2N3O4P5Q6R7S8T9U0V1W2X3Y4Z5a6b7c8d9e0f1g2h3i4j5
k6l7m8n9o0p1q2r3s4t5u6v7w8x9y0z1A2B3C4D5E6F7G8H9I0J1K2L3M4
N5O6P7Q8R9S0T1U2V3W4X5Y6Z7A8B9C0D1E2F3G4H5I6J7K8L9M0N1O2P3
Q4R5S6T7U8V9W0X1Y2Z3A4B5C6D7E8F9G0H1I2J3K4L5M6N7O8P9Q0R1S2
T3U4V5W6X7Y8Z9A0B1C2D3E4F5G6H7I8J9K0L1M2N3O4P5Q6R7S8T9U0V1
W2X3Y4Z5
-----END PUBLIC KEY-----', // 固定的公钥
    'callback_url' => 'https://example.com/callback', // 回调URL
    'redirect_url' => 'https://example.com/redirect', // 返回URL
    'apiEndpoint' => 'https://gateway-stg.wonder.today', // API端点，测试环境
    'skipSignature' => false // 是否跳过签名验证
];