document.addEventListener('DOMContentLoaded', function() {
    // Xử lý menu mobile
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('open');
        });
    }
    
    // Xử lý nút back to top
    const backToTop = document.getElementById('back-to-top');
    
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 100) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        
        backToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Xử lý thêm vào giỏ hàng
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            const quantity = document.querySelector(`input[name="quantity"][data-product-id="${productId}"]`)?.value || 1;
            
            try {
                const response = await fetch('/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Cập nhật số lượng sản phẩm trong giỏ hàng
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Hiển thị thông báo thành công
                    showNotification('Đã thêm sản phẩm vào giỏ hàng', 'success');
                } else {
                    showNotification(data.message || 'Có lỗi xảy ra', 'error');
                }
            } catch (error) {
                showNotification('Có lỗi xảy ra', 'error');
            }
        });
    });
    
    // Xử lý cập nhật giỏ hàng
    const updateCartButtons = document.querySelectorAll('.update-cart');
    
    updateCartButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            const quantity = document.querySelector(`input[name="quantity"][data-product-id="${productId}"]`).value;
            
            try {
                const response = await fetch('/update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Cập nhật tổng tiền
                    const totalAmount = document.getElementById('total-amount');
                    if (totalAmount) {
                        totalAmount.textContent = data.total_amount;
                    }
                    
                    showNotification('Đã cập nhật giỏ hàng', 'success');
                } else {
                    showNotification(data.message || 'Có lỗi xảy ra', 'error');
                }
            } catch (error) {
                showNotification('Có lỗi xảy ra', 'error');
            }
        });
    });
    
    // Xử lý xóa sản phẩm khỏi giỏ hàng
    const removeFromCartButtons = document.querySelectorAll('.remove-from-cart');
    
    removeFromCartButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
                return;
            }
            
            const productId = this.dataset.productId;
            
            try {
                const response = await fetch('/remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Xóa phần tử khỏi DOM
                    const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                    if (cartItem) {
                        cartItem.remove();
                    }
                    
                    // Cập nhật số lượng sản phẩm trong giỏ hàng
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Cập nhật tổng tiền
                    const totalAmount = document.getElementById('total-amount');
                    if (totalAmount) {
                        totalAmount.textContent = data.total_amount;
                    }
                    
                    showNotification('Đã xóa sản phẩm khỏi giỏ hàng', 'success');
                } else {
                    showNotification(data.message || 'Có lỗi xảy ra', 'error');
                }
            } catch (error) {
                showNotification('Có lỗi xảy ra', 'error');
            }
        });
    });
    
    // Xử lý form đăng ký nhận tin
    const newsletterForm = document.getElementById('newsletter-form');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[name="email"]').value;
            
            try {
                const response = await fetch('/subscribe_newsletter.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Đăng ký nhận tin thành công', 'success');
                    this.reset();
                } else {
                    showNotification(data.message || 'Có lỗi xảy ra', 'error');
                }
            } catch (error) {
                showNotification('Có lỗi xảy ra', 'error');
            }
        });
    }
    
    // Hàm hiển thị thông báo
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        } alert`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    // Xử lý lazy loading cho hình ảnh
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback cho trình duyệt không hỗ trợ IntersectionObserver
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
});
