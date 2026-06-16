function calculateTotal() {
    let total = 0;
    const itemRows = document.querySelectorAll('.cart-item-row');
    
    itemRows.forEach(row => {
        const checkbox = row.querySelector('.item-checkbox');
        if (checkbox && checkbox.checked) {
            const price = parseFloat(row.getAttribute('data-price')) || 0;
            const qty = parseInt(row.getAttribute('data-qty')) || 0;
            total += price * qty;
        }
    });

    // Tính toán giảm giá
    const discount = total * (appliedVoucherPercent / 100);
    let finalPrice = Math.max(0, total - discount);

    // Cập nhật giao diện
    const priceDisplay = document.getElementById('finalPriceDisplay');
    if (priceDisplay) {
        priceDisplay.innerText = new Intl.NumberFormat('vi-VN').format(finalPrice) + ' đ';
    }
    
    // Kiểm tra xem tất cả đã được chọn chưa để cập nhật trạng thái 'checkAll'
    const allCheckboxes = document.querySelectorAll('.item-checkbox');
    const checkedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
    const checkAllBtn = document.getElementById('checkAll');
    if (checkAllBtn) {
        checkAllBtn.checked = (allCheckboxes.length > 0 && allCheckboxes.length === checkedCheckboxes.length);
    }

    // Cập nhật giao diện thanh Voucher
    const voucherRow = document.querySelector('.voucher-row');
    const voucherActionText = document.getElementById('voucherActionText');
    if (voucherRow) {
        if (checkedCheckboxes.length === 0) {
            voucherRow.style.opacity = '0.5';
            voucherRow.style.cursor = 'not-allowed';
            if (voucherActionText) voucherActionText.innerHTML = 'Chọn Voucher';
            
            // Reset voucher khi không có sản phẩm nào được chọn
            if (appliedVoucherPercent > 0) {
                appliedVoucherPercent = 0;
                const formData = new FormData();
                formData.append('action', 'apply_voucher');
                formData.append('code', '');
                formData.append('discount', 0);
                fetch('xuly_capnhatgiohang.php', { method: 'POST', body: formData });
            }
        } else {
            voucherRow.style.opacity = '1';
            voucherRow.style.cursor = 'pointer';
            if (voucherActionText) {
                if (appliedVoucherPercent > 0) {
                    voucherActionText.innerHTML = '<span style="color: #ee4d2d; font-weight: bold;">- ' + new Intl.NumberFormat('vi-VN').format(discount) + ' đ</span>';
                } else {
                    voucherActionText.innerHTML = 'Chọn Voucher';
                }
            }
        }
    }

    // Lưu trạng thái
    saveCheckboxStates();
}

function toggleAllCheckboxes() {
    const checkAllBtn = document.getElementById('checkAll');
    const isChecked = checkAllBtn.checked;
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    
    itemCheckboxes.forEach(cb => {
        cb.checked = isChecked;
    });
    
    calculateTotal();
}

function saveCheckboxStates() {
    const states = {};
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        states[cb.getAttribute('data-key')] = cb.checked;
    });
    sessionStorage.setItem('cartCheckboxStates', JSON.stringify(states));
}

function loadCheckboxStates() {
    const statesStr = sessionStorage.getItem('cartCheckboxStates');
    if (statesStr) {
        const states = JSON.parse(statesStr);
        document.querySelectorAll('.item-checkbox').forEach(cb => {
            const key = cb.getAttribute('data-key');
            if (states.hasOwnProperty(key)) {
                cb.checked = states[key];
            }
        });
    }
}

function openPaymentModal() {
    const checkedItems = document.querySelectorAll('.item-checkbox:checked');
    if (checkedItems.length === 0) {
        alert("Vui lòng chọn ít nhất 1 sản phẩm để thanh toán.");
        return;
    }
    document.getElementById('paymentModalOverlay').style.display = 'block';
    document.getElementById('paymentModal').style.display = 'block';
}

function closePaymentModal() {
    document.getElementById('paymentModalOverlay').style.display = 'none';
    document.getElementById('paymentModal').style.display = 'none';
}

// Khởi tạo khi load trang
document.addEventListener('DOMContentLoaded', () => {
    loadCheckboxStates();
    calculateTotal();
});
