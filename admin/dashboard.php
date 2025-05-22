<?php
require_once '../config/database.php';
require_once 'auth.php';

// Lấy tổng số đơn hàng
$stmt = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
$total_orders = $stmt->fetch()['total_orders'];

// Lấy tổng doanh thu
$stmt = $conn->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE status = 'completed'");
$total_revenue = $stmt->fetch()['total_revenue'] ?? 0;

// Lấy số lượng sản phẩm
$stmt = $conn->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $stmt->fetch()['total_products'];

// Lấy số lượng khách hàng
$stmt = $conn->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
$total_users = $stmt->fetch()['total_users'];

// Lấy đơn hàng mới nhất
$stmt = $conn->query("SELECT o.*, u.name as user_name FROM orders o 
                     JOIN users u ON o.user_id = u.id 
                     ORDER BY o.created_at DESC LIMIT 5");
$recent_orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Nike Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-black text-white w-64 py-6 flex flex-col">
            <div class="px-6 mb-8">
                <h1 class="text-2xl font-bold">NIKE ADMIN</h1>
            </div>
            <nav class="flex-1">
                <a href="dashboard.php" class="flex items-center px-6 py-3 bg-gray-900">
                    <span>Dashboard</span>
                </a>
                <a href="products.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <span>Sản phẩm</span>
                </a>
                <a href="orders.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <span>Đơn hàng</span>
                </a>
                <a href="users.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <span>Khách hàng</span>
                </a>
                <a href="categories.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <span>Danh mục</span>
                </a>
            </nav>
            <div class="px-6 py-4">
                <a href="../logout.php" class="flex items-center text-red-400 hover:text-red-300">
                    <span>Đăng xuất</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <div class="container mx-auto px-6 py-8">
                <h2 class="text-3xl font-semibold text-gray-800 mb-8">Dashboard</h2>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-gray-500 text-sm font-semibold mb-2">Tổng đơn hàng</h3>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_orders); ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-gray-500 text-sm font-semibold mb-2">Doanh thu</h3>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_revenue); ?>₫</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-gray-500 text-sm font-semibold mb-2">Sản phẩm</h3>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_products); ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-gray-500 text-sm font-semibold mb-2">Khách hàng</h3>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_users); ?></p>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Đơn hàng gần đây</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left bg-gray-50">
                                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Mã đơn hàng
                                        </th>
                                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Khách hàng
                                        </th>
                                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Tổng tiền
                                        </th>
                                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Trạng thái
                                        </th>
                                        <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Ngày đặt
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            #<?php echo $order['id']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo htmlspecialchars($order['user_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo number_format($order['total_amount']); ?>₫
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
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
                                                <?php
                                                switch($order['status']) {
                                                    case 'pending':
                                                        echo 'Chờ xử lý';
                                                        break;
                                                    case 'processing':
                                                        echo 'Đang xử lý';
                                                        break;
                                                    case 'completed':
                                                        echo 'Hoàn thành';
                                                        break;
                                                    case 'cancelled':
                                                        echo 'Đã hủy';
                                                        break;
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
</body>
</html>
