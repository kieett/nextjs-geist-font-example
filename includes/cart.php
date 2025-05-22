<?php
class Cart {
    private $conn;
    private $user_id;
    
    public function __construct($conn, $user_id = null) {
        $this->conn = $conn;
        $this->user_id = $user_id;
    }
    
    // Thêm sản phẩm vào giỏ hàng
    public function addItem($product_id, $quantity = 1) {
        try {
            // Kiểm tra số lượng tồn kho
            $stmt = $this->conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ];
            }
            
            if ($product['stock'] < $quantity) {
                return [
                    'success' => false,
                    'message' => 'Số lượng sản phẩm trong kho không đủ'
                ];
            }
            
            // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
            $stmt = $this->conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$this->user_id, $product_id]);
            $cart_item = $stmt->fetch();
            
            if ($cart_item) {
                // Cập nhật số lượng
                $new_quantity = $cart_item['quantity'] + $quantity;
                if ($new_quantity > $product['stock']) {
                    return [
                        'success' => false,
                        'message' => 'Số lượng sản phẩm trong kho không đủ'
                    ];
                }
                
                $stmt = $this->conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$new_quantity, $this->user_id, $product_id]);
            } else {
                // Thêm sản phẩm mới vào giỏ hàng
                $stmt = $this->conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$this->user_id, $product_id, $quantity]);
            }
            
            return [
                'success' => true,
                'cart_count' => $this->getCartCount()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
    
    // Cập nhật số lượng sản phẩm trong giỏ hàng
    public function updateItem($product_id, $quantity) {
        try {
            // Kiểm tra số lượng tồn kho
            $stmt = $this->conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ];
            }
            
            if ($product['stock'] < $quantity) {
                return [
                    'success' => false,
                    'message' => 'Số lượng sản phẩm trong kho không đủ'
                ];
            }
            
            // Cập nhật số lượng
            $stmt = $this->conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $this->user_id, $product_id]);
            
            return [
                'success' => true,
                'total_amount' => $this->getTotalAmount()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
    
    // Xóa sản phẩm khỏi giỏ hàng
    public function removeItem($product_id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$this->user_id, $product_id]);
            
            return [
                'success' => true,
                'cart_count' => $this->getCartCount(),
                'total_amount' => $this->getTotalAmount()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
    
    // Lấy danh sách sản phẩm trong giỏ hàng
    public function getItems() {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.*, p.name, p.price, p.image, p.stock 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ?
            ");
            $stmt->execute([$this->user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Lấy số lượng sản phẩm trong giỏ hàng
    public function getCartCount() {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
            $stmt->execute([$this->user_id]);
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Tính tổng tiền giỏ hàng
    public function getTotalAmount() {
        try {
            $stmt = $this->conn->prepare("
                SELECT SUM(c.quantity * p.price) as total 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ?
            ");
            $stmt->execute([$this->user_id]);
            return $stmt->fetch()['total'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Xóa toàn bộ giỏ hàng
    public function clear() {
        try {
            $stmt = $this->conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$this->user_id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Kiểm tra số lượng tồn kho của tất cả sản phẩm trong giỏ hàng
    public function validateStock() {
        try {
            $items = $this->getItems();
            $errors = [];
            
            foreach ($items as $item) {
                if ($item['quantity'] > $item['stock']) {
                    $errors[] = "Sản phẩm '{$item['name']}' chỉ còn {$item['stock']} sản phẩm trong kho";
                }
            }
            
            return [
                'valid' => empty($errors),
                'errors' => $errors
            ];
        } catch (PDOException $e) {
            return [
                'valid' => false,
                'errors' => ['Có lỗi xảy ra khi kiểm tra tồn kho']
            ];
        }
    }
}
?>
