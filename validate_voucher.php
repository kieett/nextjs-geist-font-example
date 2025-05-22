<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$voucher_code = $data['voucher'] ?? '';

if (empty($voucher_code)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã voucher']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT * FROM vouchers 
        WHERE code = ? 
        AND status = 1 
        AND used_count < usage_limit 
        AND expiration_date > NOW()
    ");
    
    $stmt->execute([$voucher_code]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($voucher) {
        echo json_encode([
            'success' => true,
            'discount_percentage' => $voucher['discount_percentage'],
            'message' => "Áp dụng thành công! Giảm {$voucher['discount_percentage']}%"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Mã voucher không hợp lệ hoặc đã hết hạn'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi kiểm tra voucher'
    ]);
}
?>
