<?php
require_once 'config/database.php';
require_once 'includes/cart.php';

session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để xóa sản phẩm khỏi giỏ hàng'
    ]);
    exit;
}

// Nhận dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
    exit;
}

$product_id = $data['product_id'];

// Khởi tạo đối tượng giỏ hàng
$cart = new Cart($conn, $_SESSION['user_id']);

// Xóa sản phẩm khỏi giỏ hàng
$result = $cart->removeItem($product_id);

echo json_encode($result);
?>
