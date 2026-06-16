document.addEventListener('DOMContentLoaded', function() {
    const drilldownModal = document.getElementById('drilldownModal');
    const closeDrilldownModal = document.getElementById('closeDrilldownModal');
    const drilldownTitle = document.getElementById('drilldownTitle');
    const drilldownTableBody = document.getElementById('drilldownTableBody');
    const btnBackDrilldown = document.getElementById('btnBackDrilldown');
    
    if (!drilldownModal) return;

    // Lưu trữ lịch sử điều hướng (history)
    let navigationHistory = [];
    
    // Nút tắt modal
    closeDrilldownModal.addEventListener('click', function() {
        drilldownModal.style.display = 'none';
        navigationHistory = [];
    });
    
    // Bắt sự kiện click ngoài modal
    window.addEventListener('click', function(e) {
        if (e.target == drilldownModal) {
            drilldownModal.style.display = 'none';
            navigationHistory = [];
        }
    });
    
    // Nút quay lại
    btnBackDrilldown.addEventListener('click', function() {
        if (navigationHistory.length > 1) {
            navigationHistory.pop(); // Xóa trạng thái hiện tại
            const previousState = navigationHistory[navigationHistory.length - 1];
            fetchData(previousState.action, previousState.params, false);
        }
    });

    // Định dạng tiền
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
    }

    // Hàm gọi API
    function fetchData(action, params = {}, pushToHistory = true) {
        let url = `chitiet_thongkedoanhthu.php?action=${action}`;
        for (let key in params) {
            url += `&${key}=${params[key]}`;
        }
        
        drilldownTableBody.innerHTML = '<tr><td colspan="2" style="text-align:center; padding: 20px;">Đang tải dữ liệu...</td></tr>';
        drilldownModal.style.display = 'flex';
        
        fetch(url)
            .then(res => res.json())
            .then(res => {
                if(res.status === 'success') {
                    drilldownTitle.textContent = res.title;
                    renderTable(res.data, action, params);
                    
                    if (pushToHistory) {
                        navigationHistory.push({ action: action, params: params });
                    }
                    
                    btnBackDrilldown.style.display = navigationHistory.length > 1 ? 'inline-block' : 'none';
                } else {
                    alert('Lỗi khi lấy dữ liệu: ' + res.message);
                }
            })
            .catch(err => {
                console.error('Error fetching drilldown data:', err);
                drilldownTableBody.innerHTML = '<tr><td colspan="2" style="text-align:center;color:red; padding: 20px;">Lỗi kết nối máy chủ</td></tr>';
            });
    }
    
    // Render bảng
    function renderTable(data, currentAction, currentParams) {
        drilldownTableBody.innerHTML = '';
        if (data.length === 0) {
            drilldownTableBody.innerHTML = '<tr><td colspan="2" style="text-align:center; padding: 20px;">Không có dữ liệu</td></tr>';
            return;
        }
        
        data.forEach(item => {
            const tr = document.createElement('tr');
            
            const tdLabel = document.createElement('td');
            tdLabel.textContent = item.label;
            
            const tdRevenue = document.createElement('td');
            tdRevenue.textContent = formatCurrency(item.revenue);
            tdRevenue.style.fontWeight = 'bold';
            tdRevenue.style.color = '#10b981';
            tdRevenue.style.textAlign = 'right';
            
            tr.appendChild(tdLabel);
            tr.appendChild(tdRevenue);
            
            if (currentAction === 'get_year') {
                tr.style.cursor = 'pointer';
                tr.classList.add('hover-row');
                tr.addEventListener('click', () => {
                    const year = currentParams.year || new Date().getFullYear();
                    fetchData('get_month', { month: item.value, year: year });
                });
            } else if (currentAction === 'get_month') {
                tr.style.cursor = 'pointer';
                tr.classList.add('hover-row');
                tr.addEventListener('click', () => {
                    fetchData('get_week', { week_val: item.value });
                });
            }
            
            drilldownTableBody.appendChild(tr);
        });
    }

    const cardWeek = document.getElementById('cardRevWeek');
    const cardMonth = document.getElementById('cardRevMonth');
    const cardYear = document.getElementById('cardRevYear');
    
    if (cardWeek) {
        cardWeek.addEventListener('click', () => {
            navigationHistory = [];
            fetchData('get_week');
        });
    }
    
    if (cardMonth) {
        cardMonth.addEventListener('click', () => {
            navigationHistory = [];
            fetchData('get_month');
        });
    }
    
    if (cardYear) {
        cardYear.addEventListener('click', () => {
            navigationHistory = [];
            fetchData('get_year');
        });
    }
});
