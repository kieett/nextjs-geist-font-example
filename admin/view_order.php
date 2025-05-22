<?php
require_once '../config/database.php';
require_once 'auth.php';

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$_GET['id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image as product_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$_GET['id']]);
$order_items = $stmt->fetchAll();

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_GET['id']]);
    
    header('Location: view_order.php?id=' . $_GET['id'] . '&success=Đã cập nhật trạng thái đơn hàng');
    exit();
}

// Lấy thông tin shipping
$shipping_info = json_decode($order['shipping_address'], true);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order['id']; ?> - Nike Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-black text-white w-64 py-6 flex flex-col">
            <div class="px-6 mb-8">
                <h1 class="text-2xl font-bold">NIKE ADMIN</h1>
            </div>
            <nav class="flex-1">
                <a href="dashboard.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Dashboard</a>
                <a href="products.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Sản phẩm</a>
                <a href="orders.php" class="flex items-center px-6 py-3 bg-gray-900">Đơn hàng</a>
                <a href="users.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Khách hàng</a>
                <a href="categories.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Danh mục</a>
                <a href="posts.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Bài viết</a>
                <a href="revenue.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Doanh thu</a>
            </nav>
            <div class="px-6 py-4">
                <a href="../logout.php" class="flex items-center text-red-400 hover:text-red-300">Đăng xuất</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <div class="container mx-auto px-6 py-8">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-3xl font-semibold text-gray-800">Chi tiết đơn hàng #<?php echo $order['id']; ?></h2>
                    <a href="orders.php" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                        Quay lại
                    </a>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Order Information -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Thông tin đơn hàng</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Mã đơn hàng:</span>
                                <span class="font-medium">#<?php echo $order['id']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Ngày đặt:</span>
                                <span><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Trạng thái:</span>
                                <form action="" method="POST" class="inline-block">
                                    <select name="status" onchange="this.form.submit()"
                                            class="text-sm rounded-full px-3 py-1 
                                            <?php
                                            switch($order['status']) {
                                                case 'pending':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'processing':
                                                    echo 'bg-blue-100 text-blue-800';
                                                    break;
                                                case 'completed':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'cancelled':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                            }
                                            ?>">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>
                                            Chờ xử lý
                                        </option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>
                                            Đang xử lý
                                        </option>
                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>
                                            Hoàn thành
                                        </option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                            Đã hủy
                                        </option>
                                    </select>
                                </form>
                            </div>
                            <?php if ($order['voucher_code']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Mã giảm giá:</span>
                                <span><?php echo $order['voucher_code']; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Thông tin khách hàng</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Họ tên:</span>
                                <span><?php echo htmlspecialchars($shipping_info['name']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Email:</span>
                                <span><?php echo htmlspecialchars($shipping_info['email']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Số điện thoại:</span>
                                <span><?php echo htmlspecialchars($shipping_info['phone']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Địa chỉ:</span>
                                <span class="text-right"><?php echo htmlspecialchars($shipping_info['address']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="mt-8 bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Chi tiết sản phẩm</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn giá</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <img class="h-10 w-10 rounded object-cover" 
                                                     src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                        <?php echo number_format($item['price']); ?>₫
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                        <?php echo $item['quantity']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                        <?php echo number_format($item['price'] * $item['quantity']); ?>₫
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-500">Tạm tính:</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">
                                        <?php echo number_format($order['total_amount'] + ($order['discount_amount'] ?? 0)); ?>₫
                                    </td>
                                </tr>
                                <?php if ($order['discount_amount']): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-500">Giảm giá:</td>
                                    <td class="px-6 py-4 text-right text-sm text-red-600">
                                        -<?php echo number_format($order['discount_amount']); ?>₫
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right text-base font-medium text-gray-900">Tổng cộng:</td>
                                    <td class="px-6 py-4 text-right text-base font-bold text-gray-900">
                                        <?php echo number_format($order['total_amount']); ?>₫
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
