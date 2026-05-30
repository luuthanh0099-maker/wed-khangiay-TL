document.addEventListener("DOMContentLoaded", function() {
    const sliderContainer = document.getElementById('sliderContainer');
    const dots = document.querySelectorAll('.dot');
    const slides = document.querySelectorAll('.slide');
    let currentIndex = 0;
    const totalSlides = slides.length;

    if (totalSlides === 0) return; // Không có ảnh nào

    function goToSlide(index) {
        currentIndex = index;
        const translateX = -(currentIndex * 100);
        sliderContainer.style.transform = `translateX(${translateX}%)`;
        
        // Cập nhật trạng thái của các dấu chấm (dots)
        dots.forEach(dot => dot.classList.remove('active'));
        if(dots[currentIndex]) {
            dots[currentIndex].classList.add('active');
        }
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        goToSlide(currentIndex);
    }

    // Đặt bộ đếm thời gian tự động trượt mỗi 3 giây (3000ms)
    let slideInterval = setInterval(nextSlide, 3000);

    // Xử lý sự kiện click vào dot
    window.currentSlide = function(index) {
        goToSlide(index);
        // Khi người dùng click, reset lại interval để ảnh không bị nhảy kép
        clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, 3000);
    }
});

// Xử lý Tìm kiếm AJAX
const searchInput = document.getElementById('search-input');
const searchResults = document.getElementById('search-results');

if (searchInput) {
    searchInput.addEventListener('input', function() {
        const q = this.value.trim();
        
        if (q.length > 0) {
            fetch(`xuly_timkiem.php?q=${encodeURIComponent(q)}`)
                .then(response => response.text())
                .then(html => {
                    searchResults.innerHTML = html;
                    searchResults.style.display = 'block';
                })
                .catch(err => console.error("Lỗi tìm kiếm:", err));
        } else {
            searchResults.innerHTML = '';
            searchResults.style.display = 'none';
        }
    });
}

// Xử lý Thêm vào giỏ hàng (AJAX)
function addToCart(id, type) {
    const loginBtn = document.querySelector('a[href="dangnhap.php"].btn-outline');
    if (loginBtn) {
        alert("Mời quý khách đăng nhập trước khi mua hàng");
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('type', type);

    fetch('xuly_themgiohang.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Cập nhật số đếm giỏ hàng trên UI
            const badges = document.querySelectorAll('.cart-badge');
            badges.forEach(badge => {
                badge.innerText = data.total_items;
            });
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error("Lỗi thêm giỏ hàng:", error);
    });
}

// Xóa khỏi giỏ hàng
function removeFromCart(key) {
    if (!confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) return;
    
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('key', key);

    fetch('xuly_capnhatgiohang.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            window.location.reload(); // Tải lại trang để cập nhật tổng tiền và giao diện
        } else {
            console.error("Lỗi: " + data.message);
        }
    })
    .catch(error => {
        console.error("Lỗi xóa giỏ hàng:", error);
    });
}

// Cập nhật số lượng
function updateQty(key, newQty) {
    if (newQty < 1) {
        removeFromCart(key);
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_qty');
    formData.append('key', key);
    formData.append('qty', newQty);

    fetch('xuly_capnhatgiohang.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            window.location.reload();
        } else {
            alert(data.message);
            window.location.reload();
        }
    })
    .catch(error => {
        console.error("Lỗi cập nhật giỏ hàng:", error);
    });
}

// Xử lý Voucher
function openVoucherModal() {
    const checkedItems = document.querySelectorAll('.item-checkbox:checked');
    if (checkedItems.length === 0) {
        alert("Vui lòng chọn ít nhất 1 sản phẩm để áp dụng mã giảm giá.");
        return;
    }
    document.getElementById('voucherModalOverlay').style.display = 'block';
    document.getElementById('voucherModal').style.display = 'block';
}

function closeVoucherModal() {
    document.getElementById('voucherModalOverlay').style.display = 'none';
    document.getElementById('voucherModal').style.display = 'none';
}

function applyVoucher(code, discount) {
    const formData = new FormData();
    formData.append('action', 'apply_voucher');
    formData.append('code', code);
    formData.append('discount', discount);

    fetch('xuly_capnhatgiohang.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            window.location.reload();
        } else {
            console.error("Lỗi: " + data.message);
        }
    })
    .catch(error => {
        console.error("Lỗi áp dụng voucher:", error);
    });
}
