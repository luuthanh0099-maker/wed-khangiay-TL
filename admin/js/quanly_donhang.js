document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.btn-view-details');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (id) {
                window.location.href = 'chitiet_donhang.php?id=' + id;
            }
        });
    });
});
