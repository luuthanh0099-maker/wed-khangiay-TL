document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    
    function toggleFields() {
        if (!typeSelect) return;
        var type = typeSelect.value;
        document.querySelectorAll('.voucher-fields, .khuyenmai-fields, .thongbao-fields').forEach(el => el.style.display = 'none');
        if(type === 'khuyenmai') {
            document.querySelectorAll('.khuyenmai-fields').forEach(el => el.style.display = 'block');
        } else if(type === 'voucher') {
            document.querySelectorAll('.voucher-fields').forEach(el => el.style.display = 'block');
        } else if(type === 'thongbao') {
            document.querySelectorAll('.thongbao-fields').forEach(el => el.style.display = 'block');
        }
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', toggleFields);
        toggleFields();
    }
});
