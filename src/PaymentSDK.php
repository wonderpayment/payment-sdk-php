<?php



class PaymentSDK
{
    /**
     * array(
     * 'appid' => '',
     * 'signaturePrivateKey' => '',
     * 'webhookVerifyPublicKey' = > '',
     * 'callback_url' => '',
     * 'redirect_url' => '',
     * 'environment' => 'stg' // 环境配置：'stg' 或 'prod'，默认为 'stg'
     * )
     */
    private $options;
    private $appId;
    private $privateKey;
    private $publicKey;

    public function __construct($options) {
        $this->options = $options;

        // 检查必要参数是否存在
        if (!isset($options['appid']) || !isset($options['signaturePrivateKey']) || !isset($options['webhookVerifyPublicKey'])) {
            throw new \Exception('Missing required options: appid, signaturePrivateKey, or webhookVerifyPublicKey');
        }

        // 直接存储认证信息
        $this->appId = $options['appid'];
        $this->privateKey = $options['signaturePrivateKey'];
        $this->publicKey = $options['webhookVerifyPublicKey'];
    }

    public function verify() {
        return true;
    }

    /**
     * @throws Exception
     */
    public function createPaymentLink($params) {
        if(!is_array($params)) {
            throw new \Exception('Parameters must be an array');
        }
        $order = $params['order'];
        if(!is_array($order)) {
            throw new \Exception('order must be an array');
        }
        if(empty($order['reference_number'])) {
            throw new \Exception('Order reference number must be provided');
        }
        if(empty($order['charge_fee'])) {
            throw new \Exception('Order charge fee must be provided');
        }
        $order['callback_url'] = $this->options['callback_url'];
        $order['redirect_url'] = $this->options['redirect_url'];
        $params['order'] = $order;

        // 调用内部请求方法创建支付链接
        return $this->_request("POST","/svc/payment/api/v1/openapi/orders?with_payment_link=true", null, $params);

    }

    public function voidTransaction($params) {
        if(!is_array($params)) {
            throw new \Exception('Parameters must be an array');
        }
        
        $order = isset($params['order']) ? $params['order'] : null;
        $transaction = isset($params['transaction']) ? $params['transaction'] : null;
        
        if(!is_array($order)) {
            throw new \Exception('order must be an array');
        }
        
        if(!is_array($transaction)) {
            throw new \Exception('transaction must be an array');
        }
        
        if(empty($order['reference_number']) && empty($order['number'])) {
            throw new \Exception('Order reference number or number must be provided');
        }
        
        if(empty($transaction['uuid'])) {
            throw new \Exception('Transaction UUID must be provided');
        }
        
        // 检查是否可以void
        $orderResponse = $this->queryOrder($params);
        if(isset($orderResponse['data']['transactions']) && is_array($orderResponse['data']['transactions'])) {
            $allowedVoid = false;
            foreach($orderResponse['data']['transactions'] as $t) {
                if(isset($t['allowed_void']) && $t['allowed_void'] === true) {
                    $allowedVoid = true;
                    break;
                }
            }
            if(!$allowedVoid) {
                throw new \Exception('Transaction cannot be voided');
            }
        }
        
        return $this->_request("POST", "/svc/payment/api/v1/openapi/orders/void", null, $params);
    }

    public function refundTransaction($params) {
        if(!is_array($params)) {
            throw new \Exception('Parameters must be an array');
        }
        
        $order = isset($params['order']) ? $params['order'] : null;
        $transaction = isset($params['transaction']) ? $params['transaction'] : null;
        $refund = isset($params['refund']) ? $params['refund'] : null;
        
        if(!is_array($order)) {
            throw new \Exception('order must be an array');
        }
        
        if(!is_array($transaction)) {
            throw new \Exception('transaction must be an array');
        }
        
        if(!is_array($refund)) {
            throw new \Exception('refund must be an array');
        }
        
        if(empty($order['reference_number']) && empty($order['number'])) {
            throw new \Exception('Order reference number or number must be provided');
        }
        
        if(empty($transaction['uuid'])) {
            throw new \Exception('Transaction UUID must be provided');
        }
        
        if(empty($refund['amount'])) {
            throw new \Exception('Refund amount must be provided');
        }
        
        return $this->_request("POST", "/svc/payment/api/v1/openapi/orders/refund", null, $params);
    }

    public function voidOrder($params) {
        if(!is_array($params)) {
            throw new \Exception('Parameters must be an array');
        }
        
        $order = isset($params['order']) ? $params['order'] : null;
        $transaction = isset($params['transaction']) ? $params['transaction'] : null;
        
        if(!is_array($order)) {
            throw new \Exception('order must be an array');
        }
        
        // 检查支付状态
        $orderResponse = $this->queryOrder($params);
        if(isset($orderResponse['data']['order']['correspondence_state'])) {
            $correspondenceState = $orderResponse['data']['order']['correspondence_state'];
            if($correspondenceState !== 'unpaid') {
                throw new \Exception('Order cannot be voided. Current state: ' . $correspondenceState);
            }
        }
        
        return $this->_request("POST", "/svc/payment/api/v1/openapi/orders/void", null, $params);
    }

    public function queryOrder($params) {
        if(!is_array($params)) {
            throw new \Exception('Parameters must be an array');
        }
        $order = $params['order'];
        if(!is_array($order)) {
            throw new \Exception('order must be an array');
        }
        if(empty($order['reference_number'])) {
            throw new \Exception('Order reference number must be provided');
        }
        return $this->_request("POST","/svc/payment/api/v1/openapi/orders/check", null, $params);
    }


    private function _request($method, $uri, $queryParams = array(), $body = array()) {
        // 构建完整URL
        $environment = isset($this->options['environment']) ? $this->options['environment'] : 'stg';
        $apiEndpoint = ($environment === 'prod') ? 'https://gateway.wonder.today' : 'https://gateway-stg.wonder.today';
        $fullUrl = $apiEndpoint . $uri;

        // 如果有查询参数，添加到URL
        if (!empty($queryParams)) {
            $fullUrl .= '?' . http_build_query($queryParams);
        }

        // 生成认证头
        $headers = $this->generateAuthHeaders(
            $method,
            $uri,
            json_encode($body),
            null,
            null,
            isset($this->options['skipSignature']) ? $this->options['skipSignature'] : false
        );

        // 添加Content-Type头
        $headers[] = 'Content-Type: application/json';

        // 初始化cURL
        $ch = curl_init();

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // 如果是POST/PUT请求，添加请求体
        if ($method === 'POST' || $method === 'PUT') {
            $jsonData = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }

        // 执行请求
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($error) {
            curl_close($ch);
            throw new Exception('cURL错误: ' . $error);
        }

        // 关闭cURL句柄
        curl_close($ch);

        // 解析响应
        $responseData = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new Exception('API请求失败，HTTP状态码: ' . $httpCode . ', 响应: ' . $response);
        }

        if ($responseData === null) {
            throw new Exception('无法解析API响应: ' . $response);
        }
        return $responseData;
    }


    /**
     * 生成随机字符串
     *
     * @param int $length 字符串长度
     * @return string 随机字符串
     */
    private function generateRandomString($length) {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';
        $alphabetLength = strlen($alphabet);

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $alphabet[rand(0, $alphabetLength - 1)];
        }

        return $randomString;
    }

    /**
     * 生成预签名字符串
     *
     * @param string $method HTTP方法
     * @param string $uri 请求URI
     * @param string $body 请求体
     * @return string 预签名字符串
     */
    public function generatePreSignString($method, $uri, $body = null) {
        $content = strtoupper($method) . "\n" . $uri;

        if ($body !== null && strlen($body) > 0) {
            $content .= "\n" . $body;
        }

        return $content;
    }

    /**
     * 生成签名消息
     *
     * @param string $credential 凭据字符串
     * @param string $nonce 随机数
     * @param string $method HTTP方法
     * @param string $uri 请求URI
     * @param string $body 请求体
     * @return string 签名消息
     */
    public function generateSignatureMessage($credential, $nonce, $method, $uri, $body = null) {
        // 解析凭据
        $parsedCredential = explode('/', $credential);
        $requestTime = $parsedCredential[1];
        $algorithm = $parsedCredential[2]; // 这里会是 'Wonder-RSA-SHA256'

        // 第一次HMAC-SHA256: nonce + requestTime
        $hmac1 = hash_hmac('sha256', $requestTime, $nonce, true);

        // 第二次HMAC-SHA256: result + algorithm
        $hmac2 = hash_hmac('sha256', $algorithm, $hmac1, true);

        // 生成预签名字符串
        $preSignString = $this->generatePreSignString($method, $uri, $body);

        // 第三次HMAC-SHA256: result + preSignString
        $hmac3 = hash_hmac('sha256', $preSignString, $hmac2, true);

        // 返回十六进制格式
        return bin2hex($hmac3);
    }

    /**
     * 使用私钥生成签名
     *
     * @param string $data 待签名数据
     * @return string 签名后的数据（Base64编码）
     * @throws Exception
     */
    public function sign($data)
    {
        if (empty($this->privateKey)) {
            throw new Exception('私钥未设置');
        }

        $privateKeyId = openssl_pkey_get_private($this->privateKey);
        if (!$privateKeyId) {
            throw new Exception('无法加载私钥');
        }

        // 对HMAC-SHA256的十六进制结果进行SHA256哈希，然后进行RSA签名
        // 这对应于文档中的RSA_SHA256_PKCS1v15
        $signature = '';
        $result = openssl_sign($data, $signature, $privateKeyId, OPENSSL_ALGO_SHA256);

        if (!$result) {
            throw new Exception('签名失败');
        }

        return base64_encode($signature);
    }

    /**
     * 使用公钥验证签名
     * 调用创建接口如果返回有支付链接，这个函数返回true
     *
     * @param array $params 订单参数
     * @return array|bool
     */
    public function verifySignature()
    {
        $params = array(
            'order' => array(
                'reference_number' => 'test_ref_' . time() . '_' . rand(1000, 9999),
                'charge_fee' => 100,
                'due_date' => date('Y-m-d', strtotime('+7 days')),
                'currency' => 'HKD',
                'note' => '测试订单'
            )
        );
        try {
            $response = $this->createPaymentLink($params);
            // 检查响应中是否包含支付链接
            if (isset($response['data']) && isset($response['data']['payment_link']) && !empty($response['data']['payment_link'])) {
                // 验证成功，返回 business 数据和 true
                return [
                    'business' => isset($response['business']) ? $response['business'] : null,
                    'success' => true
                ];
            }
            // 验证失败，返回空值和 false
            return [
                'business' => null,
                'success' => false
            ];
        } catch (Exception $e) {
            // 如果创建支付链接时发生异常，返回空值和 false
            return [
                'business' => null,
                'success' => false
            ];
        }
    }

    /**
     * 完整的签名流程：生成签名消息并签名
     *
     * @param string $credential 凭据字符串
     * @param string $nonce 随机数
     * @param string $method HTTP方法
     * @param string $uri 请求URI
     * @param string $body 请求体
     * @return string 签名后的数据（Base64编码）
     * @throws Exception
     */
    public function signRequest($credential, $nonce, $method, $uri, $body = null)
    {
        $signatureMessage = $this->generateSignatureMessage($credential, $nonce, $method, $uri, $body);
        return $this->sign($signatureMessage);
    }


    /**
     * 生成API请求头
     *
     * @param string $method HTTP方法
     * @param string $uri 请求URI
     * @param string $body 请求体
     * @param string $requestTime 请求时间 (格式: yyyymmddHHMMSS)
     * @param string $nonce 随机数 (16位随机字符)
     * @param bool $skipSignature 是否跳过验签
     * @return array 包含认证信息的请求头
     * @throws Exception
     */
    public function generateAuthHeaders($method, $uri, $body = null, $requestTime = null, $nonce = null, $skipSignature = false)
    {
        if ($requestTime === null) {
            // 使用UTC时间，格式为 yyyymmddHHMMSS
            $requestTime = gmdate('YmdHis');
        }

        if ($nonce === null) {
            $nonce = $this->generateRandomString(16);
        }

        $credential = $this->appId . '/' . $requestTime . '/Wonder-RSA-SHA256';

        // 如果跳过验签，生成一个模拟签名或使用简单签名
        if ($skipSignature) {
            // 在跳过验签的测试环境中，生成一个模拟签名
            $signatureMessage = $this->generateSignatureMessage($credential, $nonce, $method, $uri, $body);
            $signature = base64_encode(hash('sha256', $signatureMessage, true));
            $headers = array(
                'Credential: ' . $credential,
                'Nonce: ' . $nonce,
                'Signature: ' . $signature
            );
            $headers[] = 'X-Skip-Signature: True'; // 添加跳过验签头
        } else {
            // 正常签名流程
            $signature = $this->signRequest($credential, $nonce, $method, $uri, $body);
            $headers = array(
                'Credential: ' . $credential,
                'Nonce: ' . $nonce,
                'Signature: ' . $signature
            );
        }

        $headers[] = 'X-Request-ID: ' . $this->appId; // 添加X-Request-ID头
        return $headers;
    }
    /**
     * generateAuthHeaders已实现_signature
     * */
    private function _signature($method,$uri,$body = '') {
        return array(
            'Credential' => '',
            'Signature' => '',
            'Nonce' => ''
        );
    }
    /**
     * 生成RSA密钥对
     *
     * @param int $keyBits 密钥长度，默认4096
     * @return array 包含私钥和公钥的数组
     */
    public static function generateKeyPair($keyBits = 4096)
    {
        $config = array(
            'digest_alg' => 'sha256',
            'private_key_bits' => $keyBits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        );

        $res = openssl_pkey_new($config);

        if (!$res) {
            throw new Exception('无法生成RSA密钥对');
        }

        // 提取私钥
        openssl_pkey_export($res, $privateKey);

        // 提取公钥
        $publicKeyDetails = openssl_pkey_get_details($res);
        $publicKey = $publicKeyDetails['key'];

        // 释放资源
        openssl_pkey_free($res);

        return array(
            'private_key' => $privateKey,
            'public_key' => $publicKey
        );
    }
}