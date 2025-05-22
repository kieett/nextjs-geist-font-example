<?php
require_once 'config/database.php';

session_start();

if (!isset($_GET['id'])) {
    header('Location: /');
    exit();
}

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmt->execute([$_GET['id']]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: /');
    exit();
}

// Lấy sản phẩm liên quan
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE category_id = ? AND id != ? 
    LIMIT 4
");
$stmt->execute([$product['category_id'], $product['id']]);
$related_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Nike Store</title>
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
                <li class="flex items-center">
                    <a href="/products.php?category=<?php echo $product['category_slug']; ?>" 
                       class="hover:text-gray-800">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                    <svg class="w-3 h-3 mx-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </li>
                <li class="text-gray-800"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>

        <!-- Product Details -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Product Image -->
                <div class="p-6">
                    <div class="aspect-w-1 aspect-h-1 bg-gray-200 rounded-lg overflow-hidden">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="w-full h-full object-cover">
                    </div>
                </div>

                <!-- Product Info -->
                <div class="p-6">
                    <h1 class="text-3xl font-bold text-gray-800 mb-4">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h1>
                    
                    <div class="mb-6">
                        <span class="text-2xl font-bold">
                            <?php if ($product['sale_price']): ?>
                                <span class="text-red-600">
                                    <?php echo number_format($product['sale_price']); ?>₫
                                </span>
                                <span class="text-gray-400 line-through text-xl ml-2">
                                    <?php echo number_format($product['price']); ?>₫
                                </span>
                            <?php else: ?>
                                <?php echo number_format($product['price']); ?>₫
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="prose max-w-none mb-6">
                        <p class="text-gray-600">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </p>
                    </div>

                    <?php if ($product['stock'] > 0): ?>
                        <form action="add_to_cart.php" method="POST" class="mb-6">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            
                            <div class="flex items-center space-x-4 mb-4">
                                <label class="text-gray-700">Số lượng:</label>
                                <div class="flex items-center border rounded-lg">
                                    <button type="button" 
                                            class="px-3 py-1 text-gray-600 hover:text-gray-800"
                                            onclick="updateQuantity(-1)">-</button>
                                    <input type="number" name="quantity" value="1" min="1" 
                                           max="<?php echo $product['stock']; ?>"
                                           class="w-16 px-2 py-1 text-center border-x"
                                           id="quantity">
                                    <button type="button"
                                            class="px-3 py-1 text-gray-600 hover:text-gray-800"
                                            onclick="updateQuantity(1)">+</button>
                                </div>
                                <span class="text-gray-600">
                                    Còn <?php echo $product['stock']; ?> sản phẩm
                                </span>
                            </div>

                            <button type="submit" 
                                    class="w-full bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-800 transition-colors">
                                Thêm vào giỏ hàng
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="mb-6">
                            <button class="w-full bg-gray-300 text-gray-600 px-6 py-3 rounded-lg cursor-not-allowed">
                                Hết hàng
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Features -->
                    <div class="border-t pt-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-600">Chính hãng 100%</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-gray-600">Giao hàng nhanh</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-gray-600">Đổi trả 30 ngày</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7a4 4 0 00-8 0"></path>
                                </svg>
                                <span class="text-gray-600">Thanh toán an toàn</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <section class="mt-12">
                <h2 class="text-2xl font-bold mb-6">Sản phẩm liên quan</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <?php foreach ($related_products as $related): ?>
                        <a href="product.php?id=<?php echo $related['id']; ?>" class="group">
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                <div class="aspect-w-1 aspect-h-1">
                                    <img src="<?php echo htmlspecialchars($related['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['name']); ?>"
                                         class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                                </div>
                                <div class="p-4">
                                    <h3 class="text-lg font-medium mb-2">
                                        <?php echo htmlspecialchars($related['name']); ?>
                                    </h3>
                                    <p class="font-bold">
                                        <?php if ($related['sale_price']): ?>
                                            <span class="text-red-600">
                                                <?php echo number_format($related['sale_price']); ?>₫
                                            </span>
                                            <span class="text-gray-400 line-through text-sm ml-2">
                                                <?php echo number_format($related['price']); ?>₫
                                            </span>
                                        <?php else: ?>
                                            <?php echo number_format($related['price']); ?>₫
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
    function updateQuantity(change) {
        const input = document.getElementById('quantity');
        const currentValue = parseInt(input.value);
        const newValue = currentValue + change;
        
        if (newValue >= parseInt(input.min) && newValue <= parseInt(input.max)) {
            input.value = newValue;
        }
    }
    </script>
</body>
</html>
