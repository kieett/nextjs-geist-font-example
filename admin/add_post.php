<?php
require_once '../config/database.php';
require_once 'auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $type = $_POST['type'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $image = $_POST['image'] ?? '';
    $featured = isset($_POST['featured']) ? 1 : 0;

    if (empty($title) || empty($content) || empty($type)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc';
    } else {
        try {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            
            $stmt = $conn->prepare("INSERT INTO posts (title, slug, content, type, status, image, featured, author_id) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([$title, $slug, $content, $type, $status, $image, $featured, $_SESSION['user_id']]);
            
            $success = 'Thêm bài viết thành công!';
            
            // Chuyển hướng về trang danh sách bài viết sau 2 giây
            header("refresh:2;url=posts.php");
        } catch(PDOException $e) {
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm bài viết mới - Nike Store Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Thêm TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        });
    </script>
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
                <a href="posts.php" class="flex items-center px-6 py-3 bg-gray-900">Bài viết</a>
            </nav>
            <div class="px-6 py-4">
                <a href="../logout.php" class="flex items-center text-red-400 hover:text-red-300">Đăng xuất</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <div class="container mx-auto px-6 py-8">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-3xl font-semibold text-gray-800">Thêm bài viết mới</h2>
                    <a href="posts.php" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Quay lại</a>
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

                <div class="bg-white rounded-lg shadow-md p-6">
                    <form action="" method="POST">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tiêu đề <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="title" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Loại bài viết <span class="text-red-500">*</span>
                                </label>
                                <select name="type" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                                    <option value="">Chọn loại bài viết</option>
                                    <option value="news">Tin tức</option>
                                    <option value="announcement">Thông báo</option>
                                    <option value="blog">Blog</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Hình ảnh URL
                                </label>
                                <input type="url" name="image"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nội dung <span class="text-red-500">*</span>
                                </label>
                                <textarea id="content" name="content" rows="10"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black"></textarea>
                            </div>

                            <div class="flex items-center space-x-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="featured" class="rounded border-gray-300 text-black focus:ring-black">
                                    <span class="ml-2 text-sm text-gray-600">Bài viết nổi bật</span>
                                </label>

                                <div class="flex items-center space-x-2">
                                    <label class="text-sm text-gray-600">Trạng thái:</label>
                                    <select name="status"
                                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                                        <option value="draft">Bản nháp</option>
                                        <option value="published">Xuất bản</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800">
                                Thêm bài viết
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
