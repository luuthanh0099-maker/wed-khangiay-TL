document.addEventListener('DOMContentLoaded', function() {
    // Thêm mới
    const btnAddProduct = document.getElementById('btnAddProduct');
    if (btnAddProduct) {
        btnAddProduct.addEventListener('click', function() {
            showForm('add');
        });
    }

    // Hủy
    const btnCancelProduct = document.getElementById('btnCancelProduct');
    if (btnCancelProduct) {
        btnCancelProduct.addEventListener('click', function() {
            hideForm();
        });
    }

    // Sửa sản phẩm
    const editButtons = document.querySelectorAll('.btn-edit-product');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const dataStr = this.getAttribute('data-product');
            if (dataStr) {
                const data = JSON.parse(dataStr);
                editProduct(data);
            }
        });
    });

    function showForm(action) {
        document.getElementById('productForm').classList.add('active');
        document.getElementById('formAction').value = action;
        if(action === 'add') {
            document.getElementById('formTitle').innerText = 'Thêm mới';
            document.getElementById('formId').value = '';
            document.getElementById('formTen').value = '';
            document.getElementById('formGia').value = '';
            document.getElementById('formSoLuong').value = '0';
            document.getElementById('formMoTa').value = '';
            document.getElementById('formOldImage').value = '';
            document.getElementById('imagePreview').innerHTML = '';
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function hideForm() {
        document.getElementById('productForm').classList.remove('active');
    }

    function editProduct(data) {
        showForm('edit');
        document.getElementById('formTitle').innerText = 'Sửa ' + data.ten;
        document.getElementById('formId').value = data.id;
        document.getElementById('formTen').value = data.ten;
        document.getElementById('formGia').value = data.gia;
        document.getElementById('formSoLuong').value = data.so_luong;
        document.getElementById('formMoTa').value = data.mo_ta || '';
        document.getElementById('formOldImage').value = data.hinhanh || '';
        
        if(data.hinhanh) {
            document.getElementById('imagePreview').innerHTML = '<img src="../' + data.hinhanh + '" style="height:50px; border-radius:4px;"> <span style="font-size:12px;color:#666;">(Ảnh hiện tại)</span>';
        } else {
            document.getElementById('imagePreview').innerHTML = '';
        }
    }
});
