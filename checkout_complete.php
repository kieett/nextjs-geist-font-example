<?php
require_once 'config/database.php';
require_once 'includes/order.php';

session_start();

if (!isset($_SESSION['order_success']) || !isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

// Xóa flag đặt hàng thành công
unset($_SESSION['order_success']);

$order = new Order($conn);
$order_id = $_GET['order_id'];
$order_details = $order->getOrderDetails($order_id);

if (!$order_details) {
    header('Location: index.php');
    exit();
}

$shipping_info = json_decode($order_details['shipping_address'], true);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - Nike Store</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="text-center mb-8">
                    <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Đặt hàng thành công!</h1>
                    <p class="text-gray-600">
                        Cảm ơn bạn đã mua hàng tại Nike Store. 
                        Chúng tôi đã gửi email xác nhận đơn hàng đến <?php echo htmlspecialchars($shipping_info['email']); ?>
                    </p>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <h2 class="text-xl font-semibold mb-4">Chi tiết đơn hàng #<?php echo $order_id; ?></h2>
                    
                    <div class="mb-6">
                        <h3 class="font-medium text-gray-800 mb-2">Thông tin giao hàng:</h3>
                        <p class="text-gray-600">
                            <?php echo htmlspecialchars($shipping_info['name']); ?><br>
                            <?php echo htmlspecialchars($shipping_info['phone']); ?><br>
                            <?php echo htmlspecialchars($shipping_info['address']); ?>
                        </p>
                    </div>

                    <div class="mb-6">
                        <h3 class="font-medium text-gray-800 mb-2">Sản phẩm:</h3>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($order_details['items'] as $item): ?>
                                <div class="py-4 flex justify-between">
                                    <div>
                                        <p class="text-gray-800"><?php echo htmlspecialchars($item['name']); ?></p>
                                        <p class="text-gray-600">Số lượng: <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <p class="text-gray-600"><?php echo number_format($item['price']); ?>₫</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Tạm tính:</span>
                            <span class="text-gray-600">
                                <?php echo number_format($order_details['total_amount'] + ($order_details['discount_amount'] ?? 0)); ?>₫
                            </span>
                        </div>
                        
                        <?php if (!empty($order_details['voucher_code'])): ?>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Mã giảm giá (<?php echo htmlspecialchars($order_details['voucher_code']); ?>):</span>
                                <span class="text-gray-600">-<?php echo number_format($order_details['discount_amount']); ?>₫</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Phí vận chuyển:</span>
                            <span class="text-gray-600">Miễn phí</span>
                        </div>
                        
                        <div class="flex justify-between font-medium text-gray-800 text-lg mt-4">
                            <span>Tổng cộng:</span>
                            <span><?php echo number_format($order_details['total_amount']); ?>₫</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="/" class="inline-block bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-800">
                    Tiếp tục mua sắm
                </a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
