<?php



class PaymentSDK
{
    /**
     * array(
     * 'appid' => '',
     * 'signaturePrivateKey' => '',
     * 'webhookVerifyPublicKey' = > '',
     * 'callback_url' => '',
     * 'redirect_url' => ''
     * )
     */
    private $options;
    public function __construct($options) {
        $this->options = $options;
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
        if(!$order['reference_number']) {
            throw new \Exception('Order reference number must be provided');
        }
        if(!$order['charge_fee']) {
            throw new \Exception('Order charge fee must be provided');
        }
        $order['callback_url'] = $this->options['callback_url'];
        $order['redirect_url'] = $this->options['redirect_url'];
        $params['order'] = $order;

        $response = $this->_request("POST","/svc/payment/api/v1/openapi/orders?with_payment_link=true",null,$params);

    }

    public function voidTransaction($params) {}

    public function refundTransaction($params) {}

    public function voidOrder($params) {}

    public function queryOrder($params) {}

    private function _request($method,$uri,$queryParams = array(),$body = array()) {

    }

    private function _signature($method,$uri,$body = '') {
        return array(
            'Credential' => '',
            'Signature' => '',
            'Nonce' => ''
        );
    }
}