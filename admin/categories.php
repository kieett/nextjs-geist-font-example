<?php
require_once '../config/database.php';
require_once 'auth.php';

$error = '';
$success = '';

// Xử lý thêm danh mục mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = $_POST['name'] ?? '';
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        
        if (empty($name)) {
            $error = 'Vui lòng nhập tên danh mục';
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                $stmt->execute([$name, $slug]);
                $success = 'Thêm danh mục thành công!';
            } catch(PDOException $e) {
                $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            }
        }
    }
    // Xử lý xóa danh mục
    elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? '';
        try {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Xóa danh mục thành công!';
        } catch(PDOException $e) {
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
    // Xử lý cập nhật danh mục
    elseif ($_POST['action'] === 'update') {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        
        if (empty($name)) {
            $error = 'Vui lòng nhập tên danh mục';
        } else {
            try {
                $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $id]);
                $success = 'Cập nhật danh mục thành công!';
            } catch(PDOException $e) {
                $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            }
        }
    }
}

// Lấy danh sách danh mục
$stmt = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - Nike Store Admin</title>
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
                <a href="orders.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Đơn hàng</a>
                <a href="users.php" class="flex items-center px-6 py-3 hover:bg-gray-900">Khách hàng</a>
                <a href="categories.php" class="flex items-center px-6 py-3 bg-gray-900">Danh mục</a>
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
                    <h2 class="text-3xl font-semibold text-gray-800">Quản lý danh mục</h2>
                    <button onclick="document.getElementById('addCategoryModal').classList.remove('hidden')"
                            class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800">
                        Thêm danh mục mới
                    </button>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <!-- Categories Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tên danh mục
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Slug
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Số sản phẩm
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Thao tác
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($category['slug']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">
                                        <?php echo $category['product_count']; ?> sản phẩm
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4">
                                        Sửa
                                    </button>
                                    <form action="" method="POST" class="inline-block" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Thêm danh mục mới</h3>
                <button onclick="document.getElementById('addCategoryModal').classList.add('hidden')"
                        class="text-gray-600 hover:text-gray-800">&times;</button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tên danh mục <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                </div>
                <div class="text-right">
                    <button type="submit" class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800">
                        Thêm danh mục
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Sửa danh mục</h3>
                <button onclick="document.getElementById('editCategoryModal').classList.add('hidden')"
                        class="text-gray-600 hover:text-gray-800">&times;</button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editCategoryId">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tên danh mục <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="editCategoryName" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                </div>
                <div class="text-right">
                    <button type="submit" class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800">
                        Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editCategory(category) {
        document.getElementById('editCategoryId').value = category.id;
        document.getElementById('editCategoryName').value = category.name;
        document.getElementById('editCategoryModal').classList.remove('hidden');
    }
    </script>
</body>
</html>
