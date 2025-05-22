<?php
require_once 'config/database.php';
require_once 'includes/cart.php';
require_once 'includes/order.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

try {
    $cart = new Cart($conn, $_SESSION['user_id']);
    $order = new Order($conn);
    
    // Kiểm tra tồn kho
    $stock_check = $cart->validateStock();
    if (!$stock_check['valid']) {
        $_SESSION['error'] = implode('<br>', $stock_check['errors']);
        header('Location: checkout.php');
        exit();
    }
    
    // Lấy thông tin từ form
    $shipping_info = [
        'name' => $_POST['name'],
        'phone' => $_POST['phone'],
        'email' => $_POST['email'],
        'address' => $_POST['address']
    ];
    
    // Lấy thông tin giỏ hàng
    $cart_items = $cart->getItems();
    $subtotal = $cart->getTotalAmount();
    
    // Xử lý voucher nếu có
    $discount_amount = 0;
    $voucher_code = $_POST['voucher_code'] ?? null;
    
    if ($voucher_code) {
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
            $discount_amount = ($subtotal * $voucher['discount_percentage']) / 100;
            
            // Cập nhật số lần sử dụng voucher
            $stmt = $conn->prepare("
                UPDATE vouchers 
                SET used_count = used_count + 1 
                WHERE code = ?
            ");
            $stmt->execute([$voucher_code]);
        }
    }
    
    // Tính tổng tiền sau khi áp dụng voucher
    $total_amount = $subtotal - $discount_amount;
    
    // Tạo đơn hàng
    $order_data = [
        'user_id' => $_SESSION['user_id'],
        'total_amount' => $total_amount,
        'shipping_address' => json_encode($shipping_info, JSON_UNESCAPED_UNICODE),
        'voucher_code' => $voucher_code,
        'discount_amount' => $discount_amount
    ];
    
    $order_id = $order->create($order_data);
    
    if ($order_id) {
        // Thêm chi tiết đơn hàng
        foreach ($cart_items as $item) {
            $order->addItem($order_id, $item['product_id'], $item['quantity'], $item['price']);
        }
        
        // Xóa giỏ hàng
        $cart->clear();
        
        // Gửi email xác nhận
        $to = $shipping_info['email'];
        $subject = "Xác nhận đơn hàng #" . $order_id . " - Nike Store";
        
        // Tạo nội dung email
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; padding: 20px 0; }
                .order-info { margin: 20px 0; }
                .product-list { margin: 20px 0; }
                .total { margin-top: 20px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Cảm ơn bạn đã đặt hàng!</h1>
                    <p>Đơn hàng #$order_id đã được xác nhận.</p>
                </div>
                
                <div class='order-info'>
                    <h2>Thông tin đơn hàng:</h2>
                    <p><strong>Họ tên:</strong> {$shipping_info['name']}</p>
                    <p><strong>Địa chỉ:</strong> {$shipping_info['address']}</p>
                    <p><strong>Số điện thoại:</strong> {$shipping_info['phone']}</p>
                </div>
                
                <div class='product-list'>
                    <h2>Chi tiết đơn hàng:</h2>";
        
        foreach ($cart_items as $item) {
            $message .= "
                    <p>
                        {$item['name']} x {$item['quantity']}<br>
                        Đơn giá: " . number_format($item['price']) . "₫
                    </p>";
        }
        
        $message .= "
                </div>
                
                <div class='total'>
                    <p>Tạm tính: " . number_format($subtotal) . "₫</p>";
        
        if ($discount_amount > 0) {
            $message .= "
                    <p>Mã giảm giá: $voucher_code</p>
                    <p>Giảm giá: " . number_format($discount_amount) . "₫</p>";
        }
        
        $message .= "
                    <p>Tổng cộng: " . number_format($total_amount) . "₫</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Headers cho email HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: Nike Store <no-reply@nikestore.com>' . "\r\n";
        
        // Gửi email
        mail($to, $subject, $message, $headers);
        
        // Chuyển hướng đến trang hoàn tất
        $_SESSION['order_success'] = true;
        header('Location: checkout_complete.php?order_id=' . $order_id);
        exit();
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    header('Location: checkout.php');
    exit();
}
?>
