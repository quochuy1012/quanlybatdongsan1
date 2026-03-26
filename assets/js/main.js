// JavaScript cho ứng dụng

// Xử lý lỗi console từ extension trình duyệt
(function() {
    'use strict';
    
    // Bỏ qua lỗi từ các extension
    const originalError = console.error;
    console.error = function(...args) {
        const errorMsg = args.join(' ');
        if (errorMsg.includes('onboarding') || 
            errorMsg.includes('extension') || 
            errorMsg.includes('favicon')) {
            return; // Bỏ qua lỗi này
        }
        originalError.apply(console, args);
    };
    
    // Xử lý lỗi global
    window.addEventListener('error', function(e) {
        if (e.filename && (
            e.filename.includes('extension') || 
            e.filename.includes('onboarding') ||
            e.filename.includes('chrome-extension') ||
            e.filename.includes('moz-extension')
        )) {
            e.preventDefault();
            return false;
        }
    }, true);
    
    // Xử lý promise rejection
    window.addEventListener('unhandledrejection', function(e) {
        const reason = e.reason ? e.reason.toString() : '';
        if (reason.includes('onboarding') || 
            reason.includes('extension') ||
            reason.includes('favicon')) {
            e.preventDefault();
            return false;
        }
    });
})();

// Xử lý form tìm kiếm
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit khi chọn district
    const districtSelect = document.getElementById('district');
    if (districtSelect) {
        districtSelect.addEventListener('change', function() {
            // Có thể tự động submit hoặc cập nhật kết quả
        });
    }
    
    // Xử lý click vào district card
    const districtCards = document.querySelectorAll('.district-card');
    districtCards.forEach(card => {
        card.addEventListener('click', function() {
            const district = this.querySelector('h3').textContent;
            if (districtSelect) {
                districtSelect.value = district;
                const searchForm = document.getElementById('searchForm');
                if (searchForm) {
                    searchForm.submit();
                }
            }
        });
    });
    
    // Xử lý chat
    const chatForm = document.getElementById('chatForm');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const messageInput = document.getElementById('message');
            const message = messageInput ? messageInput.value.trim() : '';
            
            if (message) {
                // Gửi tin nhắn qua AJAX
                fetch('chat_send.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'message=' + encodeURIComponent(message)
                })
                .then(response => response.json())
                .then(data => {
                    if (data && data.success) {
                        location.reload();
                    }
                })
                .catch(err => {
                    console.log('Chat error handled:', err);
                });
            }
        });
    }
    
    // Xử lý form đăng nhập
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const loginInput = document.getElementById('login');
            const passwordInput = document.getElementById('password');
            
            if (loginInput && passwordInput) {
                const login = loginInput.value.trim();
                const password = passwordInput.value;
                
                if (!login || !password) {
                    e.preventDefault();
                    alert('Vui lòng nhập đầy đủ thông tin!');
                    return false;
                }
            }
        });
    }
});

// Format số tiền
function formatPrice(price) {
    if (!price) return '0 đ/tháng';
    return new Intl.NumberFormat('vi-VN').format(price) + ' đ/tháng';
}

