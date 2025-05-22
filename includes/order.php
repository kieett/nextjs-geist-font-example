<?php
class Order {
    private $conn;
    private $user_id;
    
    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
    }
    
    // Tạo đơn hàng mới
    public function create($shipping_address, $payment_method) {
        try {
            $this->conn->beginTransaction();
            
            // Lấy thông tin giỏ hàng
            $cart = new Cart($this->conn, $this->user_id);
            $cart_items = $cart->getItems();
            
            if (empty($cart_items)) {
                throw new Exception('Giỏ hàng trống');
            }
            
            // Kiểm tra tồn kho
            $stock_validation = $cart->validateStock();
            if (!$stock_validation['valid']) {
                throw new Exception(implode("\n", $stock_validation['errors']));
            }
            
            // Tính tổng tiền
            $total_amount = $cart->getTotalAmount();
            
            // Tạo đơn hàng
            $stmt = $this->conn->prepare("
                INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status, payment_status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', 'pending', NOW())
            ");
            $stmt->execute([$this->user_id, $total_amount, $shipping_address, $payment_method]);
            $order_id = $this->conn->lastInsertId();
            
            // Thêm chi tiết đơn hàng
            $stmt = $this->conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $item) {
                // Thêm sản phẩm vào đơn hàng
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
                
                // Cập nhật số lượng tồn kho
                $update_stock = $this->conn->prepare("
                    UPDATE products 
                    SET stock = stock - ? 
                    WHERE id = ?
                ");
                $update_stock->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Xóa giỏ hàng
            $cart->clear();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'order_id' => $order_id
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Cập nhật trạng thái đơn hàng
    public function updateStatus($order_id, $status) {
        try {
            $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            
            return [
                'success' => true
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Cập nhật trạng thái thanh toán
    public function updatePaymentStatus($order_id, $status, $transaction_id = null) {
        try {
            $sql = "UPDATE orders SET payment_status = ?";
            $params = [$status];
            
            if ($transaction_id) {
                $sql .= ", momo_transaction_id = ?";
                $params[] = $transaction_id;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $order_id;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Lấy thông tin đơn hàng
    public function get($order_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.*, u.name as customer_name, u.email as customer_email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                return null;
            }
            
            // Lấy chi tiết đơn hàng
            $stmt = $this->conn->prepare("
                SELECT oi.*, p.name as product_name, p.image as product_image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order['items'] = $stmt->fetchAll();
            
            return $order;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Lấy danh sách đơn hàng của người dùng
    public function getUserOrders($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Lấy tổng số đơn hàng
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
            $stmt->execute([$this->user_id]);
            $total = $stmt->fetch()['total'];
            
            // Lấy danh sách đơn hàng
            $stmt = $this->conn->prepare("
                SELECT * FROM orders 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$this->user_id, $limit, $offset]);
            $orders = $stmt->fetchAll();
            
            return [
                'orders' => $orders,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ];
        } catch (PDOException $e) {
            return [
                'orders' => [],
                'total' => 0,
                'total_pages' => 0
            ];
        }
    }
    
    // Hủy đơn hàng
    public function cancel($order_id) {
        try {
            $this->conn->beginTransaction();
            
            // Kiểm tra trạng thái đơn hàng
            $stmt = $this->conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $this->user_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Đơn hàng không tồn tại');
            }
            
            if ($order['status'] != 'pending') {
                throw new Exception('Không thể hủy đơn hàng này');
            }
            
            // Cập nhật trạng thái đơn hàng
            $stmt = $this->conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$order_id]);
            
            // Hoàn lại số lượng tồn kho
            $stmt = $this->conn->prepare("
                SELECT product_id, quantity FROM order_items WHERE order_id = ?
            ");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll();
            
            foreach ($items as $item) {
                $update_stock = $this->conn->prepare("
                    UPDATE products 
                    SET stock = stock + ? 
                    WHERE id = ?
                ");
                $update_stock->execute([$item['quantity'], $item['product_id']]);
            }
            
            $this->conn->commit();
            
            return [
                'success' => true
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
?>
