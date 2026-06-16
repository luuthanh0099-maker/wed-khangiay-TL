// Xử lý Click vào chữ "Liên hệ"
document.getElementById('contact-trigger').addEventListener('click', function() {
    const menu = document.getElementById('contact-menu');
    const icon = document.getElementById('trigger-icon');
    
    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
        icon.className = 'fas fa-chevron-up';
    } else {
        menu.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
    }
});

// Nút 1: Hiển thị Số điện thoại
document.getElementById('btn-dt').addEventListener('click', function() {
    const display = document.getElementById('display-frame');
    const phone = this.getAttribute('data-phone');
    const phoneDisplay = this.getAttribute('data-phone-display');
    display.innerHTML = `Số điện thoại :<br><a href="tel:${phone}" class="phone-text">${phoneDisplay}</a>`;
});

// Nút 2: Hiển thị QR Zalo
document.getElementById('btn-zalo').addEventListener('click', function() {
    const display = document.getElementById('display-frame');
    display.innerHTML = '<img src="../images/qrzalo.jpg" alt="QR Zalo"><br><strong>Hãy quét mã để liên hệ</strong>';
});

// Xử lý tự động mở tab liên hệ nếu có tham số truyền trên URL (từ các icon nổi)
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const contactMethod = urlParams.get('contact');
    
    if (contactMethod) {
        // Tự động mở menu nếu đang đóng
        const menu = document.getElementById('contact-menu');
        const icon = document.getElementById('trigger-icon');
        if (menu && (menu.style.display === 'none' || menu.style.display === '')) {
            menu.style.display = 'block';
            if(icon) icon.className = 'fas fa-chevron-up';
        }

        // Tự động kích hoạt phương thức
        setTimeout(() => {
            if (contactMethod === 'dt') {
                const btnDt = document.getElementById('btn-dt');
                if(btnDt) btnDt.click();
            } else if (contactMethod === 'zalo') {
                const btnZalo = document.getElementById('btn-zalo');
                if(btnZalo) btnZalo.click();
            } else if (contactMethod === 'gmail') {
                // Hiển thị thông báo đang chuyển hướng
                const display = document.getElementById('display-frame');
                if(display) display.innerHTML = '<strong>Đang mở cửa sổ Gmail...</strong><br>Vui lòng kiểm tra tab mới.';
                
                // Tìm thẻ a chứa link gmail và click
                const gmailLink = document.querySelector('a[href*="mail.google.com"]');
                if (gmailLink) {
                    window.open(gmailLink.href, '_blank');
                }
            }
            
            // Cuộn trang xuống khu vực liên hệ
            const trigger = document.getElementById('contact-trigger');
            if(trigger) trigger.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 300); // Thêm chút delay để mượt mà hơn
    }
});
