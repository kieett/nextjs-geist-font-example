<?php
require_once 'config/database.php';

session_start();

// Xử lý filter và phân trang
$category_slug = $_GET['category'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Xây dựng query
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          JOIN categories c ON p.category_id = c.id";
$count_query = "SELECT COUNT(*) as total FROM products p JOIN categories c ON p.category_id = c.id";

$params = [];
if ($category_slug) {
    $query .= " WHERE c.slug = ?";
    $count_query .= " WHERE c.slug = ?";
    $params[] = $category_slug;
}

// Thêm ORDER BY và LIMIT
$query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Thực thi query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Đếm tổng số sản phẩm
$stmt = $conn->prepare($count_query);
$stmt->execute($category_slug ? [$category_slug] : []);
$total_products = $stmt->fetch()['total'];
$total_pages = ceil($total_products / $per_page);

// Lấy danh sách danh mục
$stmt = $conn->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

// Lấy thông tin danh mục hiện tại
$current_category = null;
if ($category_slug) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$category_slug]);
    $current_category = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_category ? htmlspecialchars($current_category['name']) : 'Tất cả sản phẩm'; ?> - Nike Store</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-['Inter']">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="text-gray-600 text-sm mb-8">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="/" class="hover:text-gray-800">Trang chủ</a>
                    <svg class="w-3 h-3 mx-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </li>
                <?php if ($current_category): ?>
                    <li class="text-gray-800"><?php echo htmlspecialchars($current_category['name']); ?></li>
                <?php else: ?>
                    <li class="text-gray-800">Tất cả sản phẩm</li>
                <?php endif; ?>
            </ol>
        </nav>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar -->
            <div class="lg:w-1/4">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Danh mục</h2>
                    <ul class="space-y-2">
                        <li>
                            <a href="/products.php" 
                               class="<?php echo !$category_slug ? 'text-black font-medium' : 'text-gray-600 hover:text-gray-800'; ?>">
                                Tất cả sản phẩm
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="/products.php?category=<?php echo $category['slug']; ?>" 
                                   class="<?php echo $category_slug === $category['slug'] ? 'text-black font-medium' : 'text-gray-600 hover:text-gray-800'; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="lg:w-3/4">
                <?php if ($current_category): ?>
                    <h1 class="text-3xl font-bold mb-8"><?php echo htmlspecialchars($current_category['name']); ?></h1>
                <?php endif; ?>

                <?php if (empty($products)): ?>
                    <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                        <p class="text-gray-600">Không tìm thấy sản phẩm nào.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <?php foreach ($products as $product): ?>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="group">
                                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                    <div class="aspect-w-1 aspect-h-1">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300">
                                    </div>
                                    <div class="p-4">
                                        <h2 class="text-lg font-medium mb-2">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </h2>
                                        <p class="text-gray-600 text-sm mb-2">
                                            <?php echo htmlspecialchars($product['category_name']); ?>
                                        </p>
                                        <div class="flex items-center justify-between">
                                            <p class="font-bold">
                                                <?php if ($product['sale_price']): ?>
                                                    <span class="text-red-600">
                                                        <?php echo number_format($product['sale_price']); ?>₫
                                                    </span>
                                                    <span class="text-gray-400 line-through text-sm ml-2">
                                                        <?php echo number_format($product['price']); ?>₫
                                                    </span>
                                                <?php else: ?>
                                                    <?php echo number_format($product['price']); ?>₫
                                                <?php endif; ?>
                                            </p>
                                            <?php if ($product['stock'] > 0): ?>
                                                <span class="text-green-600 text-sm">Còn hàng</span>
                                            <?php else: ?>
                                                <span class="text-red-600 text-sm">Hết hàng</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-8 flex justify-center">
                            <div class="flex space-x-2">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                                       class="<?php echo $page === $i ? 'bg-black text-white' : 'bg-white text-gray-600 hover:bg-gray-50'; ?> px-4 py-2 rounded-lg">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
