-- Tạo cơ sở dữ liệu
CREATE DATABASE IF NOT EXISTS nike_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nike_shop;

-- Bảng danh mục sản phẩm
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng sản phẩm
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2),
    image VARCHAR(255),
    featured BOOLEAN DEFAULT 0,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Bảng người dùng
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng đơn hàng
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Bảng chi tiết đơn hàng
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Bảng giỏ hàng
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Thêm dữ liệu mẫu cho danh mục
INSERT INTO categories (name, slug, description) VALUES
('Giày Nam', 'giay-nam', 'Các loại giày dành cho nam'),
('Giày Nữ', 'giay-nu', 'Các loại giày dành cho nữ'),
('Giày Trẻ Em', 'giay-tre-em', 'Các loại giày dành cho trẻ em'),
('Phụ Kiện', 'phu-kien', 'Các loại phụ kiện thể thao');

-- Thêm dữ liệu mẫu cho sản phẩm
INSERT INTO products (category_id, name, slug, description, price, image, featured, stock) VALUES
(1, 'Nike Air Max 2024', 'nike-air-max-2024', 'Giày thể thao Nike Air Max phiên bản 2024', 3200000, 'https://static.nike.com/a/images/t_PDP_1280_v1/f_auto,q_auto:eco/1a4e9b05-52f1-41f8-ad11-6954f1ac40b7/air-max-90-shoes-kRsBnD.png', 1, 100),
(1, 'Nike Zoom Fly 5', 'nike-zoom-fly-5', 'Giày chạy bộ Nike Zoom Fly 5', 2800000, 'https://static.nike.com/a/images/t_PDP_1280_v1/f_auto,q_auto:eco/c76e2119-acb7-4944-9085-d4f5ae2bda4a/zoom-fly-5-road-running-shoes-dxPLb2.png', 1, 50),
(2, 'Nike Air Force 1 07', 'nike-air-force-1-07', 'Giày thời trang Nike Air Force 1 07', 2500000, 'https://static.nike.com/a/images/t_PDP_1280_v1/f_auto,q_auto:eco/b7d9211c-26e7-431a-ac24-b0540fb3c00f/air-force-1-07-shoes-WrLlWX.png', 1, 75);

-- Tạo tài khoản admin mặc định
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@nike.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Tạo bảng vouchers
CREATE TABLE IF NOT EXISTS vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_percentage DECIMAL(5,2) NOT NULL,
    expiration_date DATETIME NOT NULL,
    usage_limit INT NOT NULL,
    used_count INT DEFAULT 0,
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Thêm cột voucher vào bảng orders
ALTER TABLE orders 
    ADD COLUMN voucher_code VARCHAR(50) NULL,
    ADD COLUMN discount_amount DECIMAL(10,2) NULL;

-- Thêm một số voucher mẫu
INSERT INTO vouchers (code, discount_percentage, expiration_date, usage_limit) VALUES
('WELCOME10', 10.00, '2024-12-31 23:59:59', 100),
('SUMMER20', 20.00, '2024-06-30 23:59:59', 50),
('SPECIAL30', 30.00, '2024-03-31 23:59:59', 25);

-- Tạo bảng bài viết
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT,
    image VARCHAR(255),
    type ENUM('news', 'announcement', 'blog') NOT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    featured BOOLEAN DEFAULT 0,
    author_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Tạo bảng doanh thu theo ngày
CREATE TABLE daily_revenue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    total_orders INT DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    total_items INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng doanh thu theo tháng
CREATE TABLE monthly_revenue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    month INT NOT NULL,
    total_orders INT DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    total_items INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY year_month (year, month)
);

-- Thêm một số bài viết mẫu
INSERT INTO posts (title, slug, content, type, status, featured) VALUES
('Bộ sưu tập Nike Air Max 2024 mới', 'bo-suu-tap-nike-air-max-2024-moi', 'Khám phá bộ sưu tập Nike Air Max 2024 với nhiều cải tiến mới...', 'news', 'published', 1),
('Chương trình khuyến mãi mùa hè', 'chuong-trinh-khuyen-mai-mua-he', 'Giảm giá đến 50% cho các sản phẩm mùa hè...', 'announcement', 'published', 1),
('Hướng dẫn chọn giày chạy bộ', 'huong-dan-chon-giay-chay-bo', 'Các tiêu chí để chọn một đôi giày chạy bộ phù hợp...', 'blog', 'published', 0);
