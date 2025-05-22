<?php
require_once '../config/database.php';
require_once 'auth.php';

// Lấy thống kê theo ngày
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_orders,
        SUM(total_amount) as revenue,
        SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as completed_revenue
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$stmt->execute([$start_date, $end_date]);
$daily_stats = $stmt->fetchAll();

// Lấy thống kê theo tháng
$stmt = $conn->prepare("
    SELECT 
        YEAR(created_at) as year,
        MONTH(created_at) as month,
        COUNT(*) as total_orders,
        SUM(total_amount) as revenue,
        SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as completed_revenue
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY year DESC, month DESC
");
$stmt->execute();
$monthly_stats = $stmt->fetchAll();

// Lấy thống kê sản phẩm bán chạy
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.name,
        p.image,
        COUNT(oi.id) as total_sold,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.price * oi.quantity) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    AND o.created_at BETWEEN ? AND ?
    GROUP BY p.id, p.name
    ORDER BY total_quantity DESC
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$top_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo doanh thu - Nike Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="orders.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Đơn hàng</a>
                <a href="users.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Khách hàng</a>
                <a href="categories.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Danh mục</a>
                <a href="posts.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Bài viết</a>
                <a href="revenue.php" class="flex items-center px-6 py-3 bg-gray-900">Doanh thu</a>
            </nav>
            <div class="px-6 py-4">
                <a href="../logout.php" class="flex items-center text-red-400 hover:text-red-300">Đăng xuất</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <div class="container mx-auto px-6 py-8">
                <h2 class="text-3xl font-semibold text-gray-800 mb-8">Báo cáo doanh thu</h2>

                <!-- Filter Form -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <form action="" method="GET" class="flex items-center space-x-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                            <input type="date" name="start_date" value="<?php echo $start_date; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Đến ngày</label>
                            <input type="date" name="end_date" value="<?php echo $end_date; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                        </div>
                        <div class="pt-6">
                            <button type="submit" class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800">
                                Lọc
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Revenue Chart -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-semibold mb-4">Doanh thu theo ngày</h3>
                        <canvas id="dailyChart"></canvas>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-semibold mb-4">Doanh thu theo tháng</h3>
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h3 class="text-xl font-semibold mb-4">Sản phẩm bán chạy</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng bán</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <img class="h-10 w-10 rounded object-cover" 
                                                     src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo number_format($product['total_quantity']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo number_format($product['total_revenue']); ?>₫
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

    <script>
    // Dữ liệu cho biểu đồ doanh thu theo ngày
    const dailyData = {
        labels: <?php echo json_encode(array_column(array_reverse($daily_stats), 'date')); ?>,
        datasets: [{
            label: 'Doanh thu',
            data: <?php echo json_encode(array_column(array_reverse($daily_stats), 'completed_revenue')); ?>,
            borderColor: 'rgb(0, 0, 0)',
            tension: 0.1
        }]
    };

    // Dữ liệu cho biểu đồ doanh thu theo tháng
    const monthlyData = {
        labels: <?php echo json_encode(array_map(function($item) {
            return date('m/Y', strtotime($item['year'] . '-' . $item['month'] . '-01'));
        }, array_reverse($monthly_stats))); ?>,
        datasets: [{
            label: 'Doanh thu',
            data: <?php echo json_encode(array_column(array_reverse($monthly_stats), 'completed_revenue')); ?>,
            borderColor: 'rgb(0, 0, 0)',
            tension: 0.1
        }]
    };

    // Cấu hình chung cho biểu đồ
    const config = {
        type: 'line',
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND'
                            }).format(value);
                        }
                    }
                }
            }
        }
    };

    // Khởi tạo biểu đồ
    new Chart(document.getElementById('dailyChart'), {...config, data: dailyData});
    new Chart(document.getElementById('monthlyChart'), {...config, data: monthlyData});
    </script>
</body>
</html>
