<?php
class MoMoPayment {
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
    
    public function __construct() {
        // Thông tin test MoMo
        $this->partnerCode = "MOMOXXX";  // Thay bằng Partner Code thật
        $this->accessKey = "xxx";        // Thay bằng Access Key thật
        $this->secretKey = "xxx";        // Thay bằng Secret Key thật
    }
    
    public function createPayment($orderId, $amount, $orderInfo) {
        $requestId = time() . "";
        $redirectUrl = "http://localhost:8000/checkout_complete.php";
        $ipnUrl = "http://localhost:8000/momo_ipn.php";
        $extraData = "";
        
        $rawHash = "accessKey=" . $this->accessKey .
                  "&amount=" . $amount .
                  "&extraData=" . $extraData .
                  "&ipnUrl=" . $ipnUrl .
                  "&orderId=" . $orderId .
                  "&orderInfo=" . $orderInfo .
                  "&partnerCode=" . $this->partnerCode .
                  "&redirectUrl=" . $redirectUrl .
                  "&requestId=" . $requestId .
                  "&requestType=captureWallet";
        
        $signature = hash_hmac('sha256', $rawHash, $this->secretKey);
        
        $data = [
            'partnerCode' => $this->partnerCode,
            'partnerName' => "Nike Store",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => 'captureWallet',
            'signature' => $signature
        ];
        
        $result = $this->execPostRequest($this->endpoint, json_encode($data));
        return json_decode($result, true);
    }
    
    private function execPostRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
        );
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $result = curl_exec($ch);
        
        if ($result === FALSE) {
            curl_close($ch);
            return ['errorCode' => -1, 'message' => 'Curl Error: ' . curl_error($ch)];
        }
        
        curl_close($ch);
        return $result;
    }
    
    public function verifyPayment($requestId, $orderId, $amount, $orderInfo, $orderType, $transId, $resultCode, $requestType, $extraData, $payType, $responseTime, $message, $receivedSignature) {
        $rawHash = "accessKey=" . $this->accessKey .
                  "&amount=" . $amount .
                  "&extraData=" . $extraData .
                  "&message=" . $message .
                  "&orderId=" . $orderId .
                  "&orderInfo=" . $orderInfo .
                  "&orderType=" . $orderType .
                  "&partnerCode=" . $this->partnerCode .
                  "&payType=" . $payType .
                  "&requestId=" . $requestId .
                  "&responseTime=" . $responseTime .
                  "&resultCode=" . $resultCode .
                  "&transId=" . $transId;
        
        $signature = hash_hmac('sha256', $rawHash, $this->secretKey);
        
        return $signature === $receivedSignature;
    }
}
?>
