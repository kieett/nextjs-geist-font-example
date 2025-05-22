<?php
require_once 'config/database.php';
require_once 'includes/momo.php';

// Nhận dữ liệu từ MoMo
$data = json_decode(file_get_contents('php://input'), true);

// Ghi log để debug
$logFile = 'momo_ipn.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . ': ' . print_r($data, true) . "\n", FILE_APPEND);

if ($data) {
    $momo = new MoMoPayment();
    
    // Xác thực chữ ký
    $isValidSignature = $momo->verifyPayment(
        $data['requestId'],
        $data['orderId'],
        $data['amount'],
        $data['orderInfo'],
        $data['orderType'],
        $data['transId'],
        $data['resultCode'],
        $data['requestType'],
        $data['extraData'],
        $data['payType'],
        $data['responseTime'],
        $data['message'],
        $data['signature']
    );
    
    if ($isValidSignature) {
        // Kiểm tra mã kết quả từ MoMo
        if ($data['resultCode'] == 0) {
            try {
                // Cập nhật trạng thái đơn hàng
                $stmt = $conn->prepare("UPDATE orders SET 
                    payment_status = 'completed',
                    status = 'processing',
                    momo_transaction_id = ?
                    WHERE id = ?");
                $stmt->execute([$data['transId'], $data['orderId']]);
                
                // Ghi log thành công
                file_put_contents($logFile, date('Y-m-d H:i:s') . ': Payment successful for order ' . $data['orderId'] . "\n", FILE_APPEND);
                
                // Trả về kết quả cho MoMo
                echo json_encode([
                    'message' => 'Success',
                    'status' => 'ok'
                ]);
            } catch (PDOException $e) {
                // Ghi log lỗi
                file_put_contents($logFile, date('Y-m-d H:i:s') . ': Database error: ' . $e->getMessage() . "\n", FILE_APPEND);
                
                // Trả về lỗi cho MoMo
                http_response_code(500);
                echo json_encode([
                    'message' => 'Database error',
                    'status' => 'error'
                ]);
            }
        } else {
            // Cập nhật trạng thái thanh toán thất bại
            try {
                $stmt = $conn->prepare("UPDATE orders SET 
                    payment_status = 'failed',
                    status = 'cancelled'
                    WHERE id = ?");
                $stmt->execute([$data['orderId']]);
                
                // Ghi log thất bại
                file_put_contents($logFile, date('Y-m-d H:i:s') . ': Payment failed for order ' . $data['orderId'] . ' with code ' . $data['resultCode'] . "\n", FILE_APPEND);
                
                // Trả về kết quả cho MoMo
                echo json_encode([
                    'message' => 'Payment failed',
                    'status' => 'failed'
                ]);
            } catch (PDOException $e) {
                // Ghi log lỗi
                file_put_contents($logFile, date('Y-m-d H:i:s') . ': Database error: ' . $e->getMessage() . "\n", FILE_APPEND);
                
                // Trả về lỗi cho MoMo
                http_response_code(500);
                echo json_encode([
                    'message' => 'Database error',
                    'status' => 'error'
                ]);
            }
        }
    } else {
        // Ghi log lỗi chữ ký không hợp lệ
        file_put_contents($logFile, date('Y-m-d H:i:s') . ': Invalid signature for order ' . $data['orderId'] . "\n", FILE_APPEND);
        
        // Trả về lỗi cho MoMo
        http_response_code(400);
        echo json_encode([
            'message' => 'Invalid signature',
            'status' => 'error'
        ]);
    }
} else {
    // Ghi log lỗi dữ liệu không hợp lệ
    file_put_contents($logFile, date('Y-m-d H:i:s') . ': Invalid request data' . "\n", FILE_APPEND);
    
    // Trả về lỗi cho MoMo
    http_response_code(400);
    echo json_encode([
        'message' => 'Invalid request',
        'status' => 'error'
    ]);
}
?>
