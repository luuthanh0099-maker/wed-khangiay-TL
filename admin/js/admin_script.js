document.addEventListener('DOMContentLoaded', function() {
    // 1. Xác nhận xóa chung
    const deleteButtons = document.querySelectorAll('.btn-delete-confirm');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-msg') || 'Bạn có chắc chắn muốn xóa?';
            if (!confirm(message)) {
                e.preventDefault(); // Hủy sự kiện click (không chuyển trang)
            }
        });
    });

    // 2. Click thẻ Card để chuyển trang (ở Dashboard)
    const cardLinks = document.querySelectorAll('.card-link');
    cardLinks.forEach(card => {
        card.addEventListener('click', function() {
            const href = this.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        });
    });

    // 3. Tự động submit form khi đổi trạng thái (select)
    const autoSubmitSelects = document.querySelectorAll('.auto-submit-select');
    autoSubmitSelects.forEach(select => {
        select.addEventListener('change', function() {
            if (this.form) {
                this.form.submit();
            }
        });
    });
});
