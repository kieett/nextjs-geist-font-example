<?php
require_once '../config/database.php';
require_once 'auth.php';

$error = '';
$success = '';

// Kiểm tra ID sản phẩm
if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product_id = $_GET['id'];

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit();
}

// Lấy danh sách danh mục
$stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $sale_price = $_POST['sale_price'] ?? null;
    $category_id = $_POST['category_id'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $image = $_POST['image'] ?? '';
    $featured = isset($_POST['featured']) ? 1 : 0;

    if (empty($name) || empty($price) || empty($category_id)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc';
    } else {
        try {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            
            $stmt = $conn->prepare("UPDATE products 
                                  SET name = ?, slug = ?, description = ?, price = ?, 
                                      sale_price = ?, category_id = ?, stock = ?, 
                                      image = ?, featured = ? 
                                  WHERE id = ?");
            
            $stmt->execute([$name, $slug, $description, $price, $sale_price, 
                           $category_id, $stock, $image, $featured, $product_id]);
            
            $success = 'Cập nhật sản phẩm thành công!';
            
            // Cập nhật lại thông tin sản phẩm
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
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
    <title>Chỉnh sửa sản phẩm - Nike Store Admin</title>
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
                <a href="products.php" class="flex items-center px-6 py-3 bg-gray-900">
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
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-3xl font-semibold text-gray-800">Chỉnh sửa sản phẩm</h2>
                    <a href="products.php" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                        Quay lại
                    </a>
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tên sản phẩm <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" required
                                       value="<?php echo htmlspecialchars($product['name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Danh mục <span class="text-red-500">*</span>
                                </label>
                                <select name="category_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                                    <option value="">Chọn danh mục</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"
                                                <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Giá <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="price" required min="0" step="1000"
                                       value="<?php echo $product['price']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Giá khuyến mãi
                                </label>
                                <input type="number" name="sale_price" min="0" step="1000"
                                       value="<?php echo $product['sale_price']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Số lượng trong kho
                                </label>
                                <input type="number" name="stock" min="0"
                                       value="<?php echo $product['stock']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Hình ảnh URL
                                </label>
                                <input type="url" name="image"
                                       value="<?php echo htmlspecialchars($product['image']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Mô tả sản phẩm
                                </label>
                                <textarea name="description" rows="4"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-black"><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="featured" 
                                           <?php echo $product['featured'] ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-black focus:ring-black">
                                    <span class="ml-2 text-sm text-gray-600">Sản phẩm nổi bật</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800">
                                Cập nhật sản phẩm
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
