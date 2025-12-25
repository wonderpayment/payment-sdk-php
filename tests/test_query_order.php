<?php

require_once __DIR__ . '/../src/PaymentSDK.php';
require_once __DIR__ . '/config.php';

echo "=== 测试订单查询 ===\n";

try {
    // 加载配置
    $config = require __DIR__ . '/config.php';
    
    // 创建SDK实例
    $sdk = new PaymentSDK($config);
    
    // 第一步：创建支付链接获取 reference_number
    echo "第一步：创建支付链接\n";
    echo "-------------------\n";
    
    // 准备创建支付链接的测试数据
    $testOrderData = [
        'order' => [
            'reference_number' => 'test_query_ref_' . time() . '_' . rand(1000, 9999),
            'charge_fee' => 100,
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'currency' => 'HKD',
            'note' => '测试订单 - 用于查询测试'
        ]
    ];
    
    echo "订单参考号: " . $testOrderData['order']['reference_number'] . "\n";
    echo "订单金额: " . $testOrderData['order']['charge_fee'] . " " . $testOrderData['order']['currency'] . "\n";
    echo "到期日期: " . $testOrderData['order']['due_date'] . "\n\n";
    
    // 调用创建支付链接接口
    echo "正在调用创建支付链接接口...\n";
    $createResponse = $sdk->createPaymentLink($testOrderData);
    
    // 输出创建结果
    echo "创建支付链接API响应:\n";
    echo json_encode($createResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 检查是否成功创建了支付链接
    if (!isset($createResponse['data']) || !isset($createResponse['data']['payment_link'])) {
        throw new Exception('创建支付链接失败，无法进行订单查询测试');
    }
    
    echo "✅ 成功创建支付链接!\n";
    $referenceNumber = $testOrderData['order']['reference_number'];
    $paymentLink = $createResponse['data']['payment_link'];
    $orderUuid = isset($createResponse['data']['uuid']) ? $createResponse['data']['uuid'] : null;
    $transactionUuid = isset($createResponse['data']['transaction_uuid']) ? $createResponse['data']['transaction_uuid'] : null;
    
    echo "支付链接: " . $paymentLink . "\n";
    if ($orderUuid) echo "订单UUID: " . $orderUuid . "\n";
    if ($transactionUuid) echo "交易UUID: " . $transactionUuid . "\n";
    
    // 保存订单信息供后续测试使用
    $orderInfo = [
        'reference_number' => $referenceNumber,
        'payment_link' => $paymentLink,
        'order_uuid' => $orderUuid,
        'transaction_uuid' => $transactionUuid
    ];
    file_put_contents(__DIR__ . '/last_order.json', json_encode($orderInfo));
    echo "订单信息已保存到 last_order.json\n\n";
    
    // 第二步：使用相同的 reference_number 查询订单
    echo "第二步：查询订单\n";
    echo "-------------------\n";
    echo "使用订单参考号: " . $referenceNumber . "\n\n";
    
    // 准备查询参数
    $queryParams = [
        'order' => [
            'reference_number' => $referenceNumber
        ]
    ];
    
    echo "正在调用订单查询接口...\n";
    
    // 调用订单查询接口
    $response = $sdk->queryOrder($queryParams);
    
    // 输出响应结果
    echo "订单查询API响应:\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 检查查询结果
    if (isset($response['data'])) {
        echo "✅ 订单查询成功!\n";
        $orderData = $response['data'];
        
        if (isset($orderData['status'])) {
            echo "订单状态: " . $orderData['status'] . "\n";
        }
        
        if (isset($orderData['payment_link'])) {
            echo "支付链接: " . $orderData['payment_link'] . "\n";
        }
        
        if (isset($orderData['uuid'])) {
            echo "订单UUID: " . $orderData['uuid'] . "\n";
        }
        
        // 验证查询结果与创建结果的一致性
        if (isset($orderData['reference_number']) && $orderData['reference_number'] === $referenceNumber) {
            echo "✅ 订单参考号匹配!\n";
        } else {
            echo "❌ 订单参考号不匹配!\n";
        }
        
        if (isset($orderData['payment_link']) && $orderData['payment_link'] === $paymentLink) {
            echo "✅ 支付链接匹配!\n";
        } else {
            echo "❌ 支付链接不匹配!\n";
        }
    } else {
        echo "❌ 订单查询失败或订单不存在!\n";
    }
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "错误堆栈:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== 测试完成 ===\n";
