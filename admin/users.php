<?php
require_once '../config/database.php';
require_once 'auth.php';

// Xử lý khóa/mở khóa tài khoản người dùng
if (isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'block') {
        $stmt = $conn->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
    } else if ($action === 'unblock') {
        $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    }
    
    $stmt->execute([$user_id]);
    header('Location: users.php?success=Cập nhật trạng thái người dùng thành công');
    exit();
}

// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Tổng số người dùng
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$total_users = $stmt->fetch()['total'];
$total_pages = ceil($total_users / $limit);

// Lấy danh sách người dùng
$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$limit, $offset]);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - Nike Store Admin</title>
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
                <a href="dashboard.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <span>Dashboard</span>
                </a>
                <a href="products.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <span>Sản phẩm</span>
                </a>
                <a href="orders.php" class="flex items-center px-6 py-3 hover:bg-gray-900">
                    <span>Đơn hàng</span>
                </a>
                <a href="users.php" class="flex items-center px-6 py-3 bg-gray-900">
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
                <h2 class="text-3xl font-semibold text-gray-800 mb-8">Quản lý người dùng</h2>

                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Users Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Người dùng
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Liên hệ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Trạng thái
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ngày đăng ký
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Thao tác
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </div>
                                    <?php if ($user['phone']): ?>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($user['phone']); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $user['status'] === 'active' ? 'Hoạt động' : 'Đã khóa'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" 
                                       class="text-indigo-600 hover:text-indigo-900 mr-4">
                                        Chi tiết
                                    </a>
                                    <form action="" method="POST" class="inline-block">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <?php if ($user['status'] === 'active'): ?>
                                            <input type="hidden" name="action" value="block">
                                            <button type="submit" class="text-red-600 hover:text-red-900"
                                                    onclick="return confirm('Bạn có chắc chắn muốn khóa tài khoản này?')">
                                                Khóa
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="unblock">
                                            <button type="submit" class="text-green-600 hover:text-green-900"
                                                    onclick="return confirm('Bạn có chắc chắn muốn mở khóa tài khoản này?')">
                                                Mở khóa
                                            </button>
                                        <?php endif; ?>
                                    </form>
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
                        <a href="?page=<?php echo $page - 1; ?>" 
                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            Trước
                        </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium 
                                  <?php echo $page === $i ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" 
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
