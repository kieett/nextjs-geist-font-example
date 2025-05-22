<?php
// Lấy số lượng sản phẩm trong giỏ hàng nếu đã đăng nhập
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $cart = new Cart($conn, $_SESSION['user_id']);
    $cart_count = $cart->getCartCount();
}
?>
<header class="bg-white shadow-sm sticky top-0 z-50">
    <nav class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <!-- Logo -->
            <a href="/" class="text-2xl font-bold text-black">
                NIKE
            </a>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-8">
                <?php
                $stmt = $conn->query("SELECT * FROM categories ORDER BY name");
                $nav_categories = $stmt->fetchAll();
                foreach ($nav_categories as $cat):
                ?>
                    <a href="/products.php?category=<?php echo $cat['slug']; ?>" 
                       class="text-gray-600 hover:text-black transition-colors">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- User Actions -->
            <div class="flex items-center space-x-6">
                <!-- Search -->
                <form action="/products.php" method="GET" class="hidden md:block">
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               placeholder="Tìm kiếm..."
                               class="w-64 pl-4 pr-10 py-2 border rounded-full focus:outline-none focus:border-black">
                        <button type="submit" class="absolute right-3 top-2.5">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Cart -->
                <a href="/cart.php" class="relative">
                    <svg class="w-6 h-6 text-gray-600 hover:text-black transition-colors" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <?php if ($cart_count > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-600 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">
                            <?php echo $cart_count; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- User Menu -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="relative group">
                        <button class="flex items-center space-x-1 text-gray-600 hover:text-black transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden group-hover:block">
                            <a href="/profile.php" class="block px-4 py-2 text-gray-600 hover:text-black hover:bg-gray-100">
                                Tài khoản của tôi
                            </a>
                            <a href="/orders.php" class="block px-4 py-2 text-gray-600 hover:text-black hover:bg-gray-100">
                                Đơn hàng
                            </a>
                            <hr class="my-2">
                            <a href="/logout.php" class="block px-4 py-2 text-gray-600 hover:text-black hover:bg-gray-100">
                                Đăng xuất
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="text-gray-600 hover:text-black transition-colors">
                        Đăng nhập
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>
