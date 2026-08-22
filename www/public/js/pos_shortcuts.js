// Helper functions for keyboard shortcuts
function showSelectedProductDetails() {
    if (selectedCartIndex === -1 || !cart[selectedCartIndex]) {
        showToast('لا يوجد منتج محدد', 'info');
        return;
    }

    var item = cart[selectedCartIndex];
    if (!item.product_id) {
        showToast('لا يوجد تفاصيل لهذا المنتج', 'info');
        return;
    }

    // Open product page in new window or show details modal
    window.open('?page=products&action=edit&id=' + item.product_id, '_blank');
}

function toggleSelectedUnitType() {
    if (selectedCartIndex === -1 || !cart[selectedCartIndex]) {
        showToast('لا يوجد منتج محدد', 'info');
        return;
    }

    var item = cart[selectedCartIndex];

    // If no pack type configured, can't toggle
    if (!item.product_pack_type || !item.product_pack_quantity) {
        showToast('هذا المنتج ليس له وحدة جملة', 'info');
        return;
    }

    // Toggle between قطعة and pack type
    var newUnitType = item.unit_type === 'قطعة' ? item.product_pack_type : 'قطعة';
    changeUnitType(selectedCartIndex, newUnitType);
    showToast('تم التبديل إلى: ' + newUnitType);
}
