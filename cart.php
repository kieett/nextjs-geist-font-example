<?php
require_once 'config/database.php';
require_once 'includes/cart.php';

session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Khởi tạo đối tượng giỏ hàng
$cart = new Cart($conn, $_SESSION['user_id']);

// Lấy danh sách sản phẩm trong giỏ hàng
$cart_items = $cart->getItems();
$total_amount = $cart->getTotalAmount();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - Nike Store</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Giỏ hàng</h1>

        <?php if (empty($cart_items)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <p class="text-gray-600 mb-4">Giỏ hàng của bạn đang trống</p>
                <a href="/" class="inline-block bg-black text-white px-6 py-2 rounded-lg hover:bg-gray-800">
                    Tiếp tục mua sắm
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Danh sách sản phẩm -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="p-6 cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                                    <div class="flex items-center">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="w-20 h-20 object-cover rounded">
                                        
                                        <div class="ml-4 flex-1">
                                            <h3 class="text-lg font-medium text-gray-800">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </h3>
                                            <p class="text-gray-600">
                                                <?php echo number_format($item['price']); ?>₫
                                            </p>
                                        </div>
                                        
                                        <div class="flex items-center space-x-4">
                                            <div class="flex items-center border rounded-lg">
                                                <button type="button" 
                                                        class="quantity-btn px-3 py-1" 
                                                        onclick="updateQuantity(this, -1)">
                                                    -
                                                </button>
                                                <input type="number" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       min="1" 
                                                       max="<?php echo $item['stock']; ?>"
                                                       data-product-id="<?php echo $item['product_id']; ?>"
                                                       class="w-16 px-2 py-1 text-center border-x"
                                                       onchange="updateCart(this)">
                                                <button type="button" 
                                                        class="quantity-btn px-3 py-1" 
                                                        onclick="updateQuantity(this, 1)">
                                                    +
                                                </button>
                                            </div>
                                            
                                            <button type="button" 
                                                    class="text-red-600 hover:text-red-800"
                                                    onclick="removeFromCart(<?php echo $item['product_id']; ?>)">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Tổng tiền và thanh toán -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <h2 class="text-lg font-medium text-gray-800 mb-4">Tổng đơn hàng</h2>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-gray-600">
                                <span>Tạm tính:</span>
                                <span id="subtotal"><?php echo number_format($total_amount); ?>₫</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Phí vận chuyển:</span>
                                <span>Miễn phí</span>
                            </div>
                            <div class="border-t pt-3">
                                <div class="flex justify-between font-medium text-gray-800">
                                    <span>Tổng cộng:</span>
                                    <span id="total-amount" class="text-xl">
                                        <?php echo number_format($total_amount); ?>₫
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <a href="checkout.php" class="block w-full bg-black text-white text-center px-6 py-3 rounded-lg hover:bg-gray-800">
                            Tiến hành thanh toán
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script>
        function updateQuantity(button, change) {
            const input = button.parentElement.querySelector('input');
            const currentValue = parseInt(input.value);
            const newValue = currentValue + change;
            
            if (newValue >= parseInt(input.min) && newValue <= parseInt(input.max)) {
                input.value = newValue;
                updateCart(input);
            }
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount) + '₫';
        }
    </script>
</body>
</html>
