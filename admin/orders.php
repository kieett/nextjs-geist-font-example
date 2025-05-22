<?php
require_once '../config/database.php';
require_once 'auth.php';

// Xử lý cập nhật trạng thái đơn hàng
if (isset($_POST['order_id']) && isset($_POST['status'])) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    header('Location: orders.php?success=Đã cập nhật trạng thái đơn hàng');
    exit();
}

// Xử lý filter và phân trang
$status = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng query
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id";
$params = [];

if ($status) {
    $query .= " WHERE o.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Thực thi query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Đếm tổng số đơn hàng
$count_query = "SELECT COUNT(*) as total FROM orders";
if ($status) {
    $count_query .= " WHERE status = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->execute([$status]);
} else {
    $stmt = $conn->query($count_query);
}
$total_orders = $stmt->fetch()['total'];
$total_pages = ceil($total_orders / $limit);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Nike Store Admin</title>
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
                    <h2 class="text-3xl font-semibold text-gray-800">Quản lý đơn hàng</h2>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Filter -->
                <div class="mb-6">
                    <div class="flex space-x-4">
                        <a href="orders.php" 
                           class="<?php echo !$status ? 'bg-black text-white' : 'bg-white text-gray-600'; ?> px-4 py-2 rounded-lg hover:bg-gray-800 hover:text-white">
                            Tất cả
                        </a>
                        <a href="?status=pending" 
                           class="<?php echo $status === 'pending' ? 'bg-black text-white' : 'bg-white text-gray-600'; ?> px-4 py-2 rounded-lg hover:bg-gray-800 hover:text-white">
                            Chờ xử lý
                        </a>
                        <a href="?status=processing" 
                           class="<?php echo $status === 'processing' ? 'bg-black text-white' : 'bg-white text-gray-600'; ?> px-4 py-2 rounded-lg hover:bg-gray-800 hover:text-white">
                            Đang xử lý
                        </a>
                        <a href="?status=completed" 
                           class="<?php echo $status === 'completed' ? 'bg-black text-white' : 'bg-white text-gray-600'; ?> px-4 py-2 rounded-lg hover:bg-gray-800 hover:text-white">
                            Hoàn thành
                        </a>
                        <a href="?status=cancelled" 
                           class="<?php echo $status === 'cancelled' ? 'bg-black text-white' : 'bg-white text-gray-600'; ?> px-4 py-2 rounded-lg hover:bg-gray-800 hover:text-white">
                            Đã hủy
                        </a>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mã đơn hàng
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Khách hàng
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tổng tiền
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Trạng thái
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ngày đặt
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Thao tác
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    #<?php echo $order['id']; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo number_format($order['total_amount']); ?>₫
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form action="" method="POST" class="inline-block">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
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
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                                       class="text-indigo-600 hover:text-indigo-900">
                                        Chi tiết
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-6 flex justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . $status : ''; ?>" 
                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            Trước
                        </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . $status : ''; ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium 
                                  <?php echo $page === $i ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . $status : ''; ?>" 
                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            Sau
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
