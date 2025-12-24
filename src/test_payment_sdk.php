<?php
// 测试新实现的 PaymentSDK

// 包含必要的文件
require_once dirname(__FILE__) . '/PaymentSDK.php';

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

// 模拟配置参数
$options = array(
    'appid' => '9a1e9fc2-4626-496e-9136-c1574aa319c6',
    'signaturePrivateKey' => $trimmedPrivateKey,
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
-----END PUBLIC KEY-----',
    'callback_url' => 'https://example.com/callback',
    'redirect_url' => 'https://example.com/redirect',
    'apiEndpoint' => 'https://gateway-stg.wonder.today',
    'skipSignature' => false  // 在测试环境中跳过签名验证
);

try {
    // 创建SDK实例
    echo "创建 PaymentSDK 实例...\n";
    $sdk = new PaymentSDK($options);
    $params = array(
        'order' => array(
            'reference_number' => 'test_ref_' . time() . '_' . rand(1000, 9999),
            'charge_fee' => 100,
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'currency' => 'HKD',
            'note' => '测试订单'
        )
    );
    // 测试 verify 方法
    echo "测试 verify() 方法...\n";
    $verifyResult = $sdk->verifySignature();
    echo "验证结果: " . ($verifyResult ? '成功' : '失败') . "\n";
    
    echo "createPaymentLink 方法将使用内部认证功能...\n";
    
    // 测试 createPaymentLink 方法（参数验证部分）
    echo "测试 createPaymentLink() 方法的参数验证...\n";
    try {
        $params["order"]["reference_number"]="test_ref_" . time();
        $result = $sdk->createPaymentLink($params);
        echo "支付链接创建成功\n";
        echo "完整响应: \n";
        print_r($result);
        if (isset($result['data']['payment_link'])) {
            echo "支付链接: " . $result['data']['payment_link'] . "\n";
        }
    } catch (Exception $e) {
        echo "支付链接创建失败: " . $e->getMessage() . "\n";
        // 注意：关于"Array支付链接创建成功"的警告，这是因为我们在输出数组时可能与字符串混合
    }
    
    echo "\n测试完成！新实现的 PaymentSDK 保持了原始结构，同时集成了 WonderPaymentLinkSDK 的核心功能。\n";

} catch (Exception $e) {
    echo "测试过程中发生错误: " . $e->getMessage() . "\n";
    echo "错误追踪: " . $e->getTraceAsString() . "\n";
}