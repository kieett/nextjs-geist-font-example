<?php
require_once 'config/database.php';

// Nhận dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email không hợp lệ'
    ]);
    exit;
}

$email = $data['email'];

try {
    // Kiểm tra email đã đăng ký chưa
    $stmt = $conn->prepare("SELECT id FROM newsletters WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email này đã đăng ký nhận tin'
        ]);
        exit;
    }
    
    // Thêm email vào danh sách
    $stmt = $conn->prepare("INSERT INTO newsletters (email, created_at) VALUES (?, NOW())");
    $stmt->execute([$email]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đăng ký nhận tin thành công'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?>
