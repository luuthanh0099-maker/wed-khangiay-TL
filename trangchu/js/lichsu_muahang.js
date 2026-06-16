function openDetailModal(orderData) {
    document.getElementById('modalOrderId').innerText = '#' + orderData.id;
    document.getElementById('modalPhone').innerText = orderData.phone;
    document.getElementById('modalAddress').innerText = orderData.shipping_address;
    
    // Định dạng ngày tháng
    let dateObj = new Date(orderData.created_at);
    let formattedDate = ('0' + dateObj.getDate()).slice(-2) + '/' + ('0' + (dateObj.getMonth()+1)).slice(-2) + '/' + dateObj.getFullYear() + ' ' + ('0' + dateObj.getHours()).slice(-2) + ':' + ('0' + dateObj.getMinutes()).slice(-2);
    document.getElementById('modalDate').innerText = formattedDate;
    
    // Định dạng tổng tiền
    document.getElementById('modalTotal').innerText = new Intl.NumberFormat('vi-VN').format(orderData.total) + ' đ';

    // Hiển thị sản phẩm
    let productListHtml = '';
    if (orderData.items && orderData.items.length > 0) {
        orderData.items.forEach(item => {
            let priceFormatted = new Intl.NumberFormat('vi-VN').format(item.price);
            productListHtml += `
                <li>
                    <div class="prod-name">${item.product_name} <br><span class="prod-qty">x${item.quantity}</span></div>
                    <div class="prod-price">${priceFormatted} đ</div>
                </li>
            `;
        });
    } else {
        productListHtml = '<li><div class="prod-name" style="color:#ee4d2d;">Không tìm thấy chi tiết sản phẩm (Lỗi lưu trữ)</div></li>';
    }
    document.getElementById('modalProductList').innerHTML = productListHtml;

    // Hiển thị Modal
    document.getElementById('detailModalOverlay').style.display = 'block';
    document.getElementById('detailModal').style.display = 'block';
}

function closeDetailModal() {
    document.getElementById('detailModalOverlay').style.display = 'none';
    document.getElementById('detailModal').style.display = 'none';
}
