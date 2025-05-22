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

// Lấy thông tin giỏ hàng
$cart_items = $cart->getItems();
$subtotal = $cart->getTotalAmount();

// Nếu giỏ hàng trống, chuyển về trang giỏ hàng
if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Nike Store</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Thanh toán</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Thông tin đơn hàng -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Thông tin đơn hàng</h2>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="py-4 flex items-center">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="w-16 h-16 object-cover rounded">
                                <div class="ml-4 flex-1">
                                    <h3 class="text-lg font-medium text-gray-800">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </h3>
                                    <p class="text-gray-600">
                                        Số lượng: <?php echo $item['quantity']; ?>
                                    </p>
                                    <p class="text-gray-600">
                                        <?php echo number_format($item['price']); ?>₫
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Form địa chỉ giao hàng -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Địa chỉ giao hàng</h2>
                    <form id="checkoutForm" method="POST" action="process_order.php">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Họ tên</label>
                                <input type="text" name="name" required
                                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-black">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Số điện thoại</label>
                                <input type="tel" name="phone" required
                                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-black">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" required
                                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-black">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 mb-2">Địa chỉ</label>
                                <textarea name="address" required rows="3"
                                          class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:border-black"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tổng quan đơn hàng và thanh toán -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-semibold mb-4">Tổng quan đơn hàng</h2>
                    
                    <!-- Áp dụng voucher -->
                    <div class="mb-6">
                        <label class="block text-gray-700 mb-2">Mã giảm giá</label>
                        <div class="flex space-x-2">
                            <input type="text" id="voucherCode" 
                                   class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-black"
                                   placeholder="Nhập mã giảm giá">
                            <button type="button" onclick="applyVoucher()"
                                    class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800">
                                Áp dụng
                            </button>
                        </div>
                        <p id="voucherMessage" class="mt-2 text-sm"></p>
                    </div>
                    
                    <!-- Chi tiết thanh toán -->
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-gray-600">
                            <span>Tạm tính:</span>
                            <span><?php echo number_format($subtotal); ?>₫</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Giảm giá:</span>
                            <span id="discountAmount">0₫</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Phí vận chuyển:</span>
                            <span>Miễn phí</span>
                        </div>
                        <div class="border-t pt-3">
                            <div class="flex justify-between font-medium text-gray-800">
                                <span>Tổng cộng:</span>
                                <span id="totalAmount" class="text-xl">
                                    <?php echo number_format($subtotal); ?>₫
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Nút thanh toán -->
                    <button type="submit" form="checkoutForm"
                            class="w-full bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-800">
                        Đặt hàng
                    </button>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
    let appliedVoucher = null;
    const subtotal = <?php echo $subtotal; ?>;

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + '₫';
    }

    function applyVoucher() {
        const voucherCode = document.getElementById('voucherCode').value.trim();
        const messageElement = document.getElementById('voucherMessage');
        
        if (!voucherCode) {
            messageElement.textContent = 'Vui lòng nhập mã giảm giá';
            messageElement.className = 'mt-2 text-sm text-red-600';
            return;
        }

        fetch('validate_voucher.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ voucher: voucherCode })
        })
        .then(response => response.json())
        .then(data => {
            messageElement.textContent = data.message;
            
            if (data.success) {
                messageElement.className = 'mt-2 text-sm text-green-600';
                appliedVoucher = {
                    code: voucherCode,
                    discount_percentage: data.discount_percentage
                };
                updateTotals();
            } else {
                messageElement.className = 'mt-2 text-sm text-red-600';
                appliedVoucher = null;
                updateTotals();
            }
        })
        .catch(error => {
            messageElement.textContent = 'Có lỗi xảy ra, vui lòng thử lại';
            messageElement.className = 'mt-2 text-sm text-red-600';
            appliedVoucher = null;
            updateTotals();
        });
    }

    function updateTotals() {
        const discountAmountElement = document.getElementById('discountAmount');
        const totalAmountElement = document.getElementById('totalAmount');
        
        let discount = 0;
        if (appliedVoucher) {
            discount = (subtotal * appliedVoucher.discount_percentage) / 100;
        }
        
        const total = subtotal - discount;
        
        discountAmountElement.textContent = formatCurrency(discount);
        totalAmountElement.textContent = formatCurrency(total);
        
        // Thêm thông tin voucher vào form
        let voucherInput = document.getElementById('appliedVoucherCode');
        if (!voucherInput) {
            voucherInput = document.createElement('input');
            voucherInput.type = 'hidden';
            voucherInput.id = 'appliedVoucherCode';
            voucherInput.name = 'voucher_code';
            document.getElementById('checkoutForm').appendChild(voucherInput);
        }
        voucherInput.value = appliedVoucher ? appliedVoucher.code : '';
    }
    </script>
</body>
</html>
