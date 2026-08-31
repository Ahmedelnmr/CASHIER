// ==========================================
// CORE APP UTILITIES (ES5 - Chrome 57 Compatible)
// ==========================================

// Modal Controls
function openModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

// Toast Notifications
function showToast(message, type) {
    type = type || 'success';
    var container = document.getElementById('toastContainer');
    if (!container) return;

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = message;

    var bg = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#3b82f6');
    toast.style.cssText =
        'background:' + bg + ';' +
        'color:white;' +
        'padding:12px 20px;' +
        'border-radius:6px;' +
        'margin-bottom:10px;' +
        'box-shadow:0 4px 8px rgba(0,0,0,0.2);' +
        'animation:slideIn 0.3s ease;';

    container.appendChild(toast);

    setTimeout(function () {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(function () {
            if (toast.parentNode) toast.remove();
        }, 300);
    }, 3000);
}

// HTML escape utility
function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Debounce utility
function debounce(func, wait) {
    var timeout;
    return function () {
        var context = this;
        var args = arguments;
        var later = function () {
            clearTimeout(timeout);
            func.apply(context, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// API Request Helper (XMLHttpRequest for PHPDesktop compatibility)
function apiRequest(url, options) {
    options = options || {};
    return new Promise(function(resolve) {
        var xhr = new XMLHttpRequest();
        var method = options.method || 'GET';
        xhr.open(method, url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        if (method !== 'GET') {
            xhr.setRequestHeader('Content-Type', 'application/json');
        }

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                // Handle HTTP errors first
                if (xhr.status === 401) {
                    resolve({ success: false, message: 'انتهت الجلسة - يرجى إعادة تسجيل الدخول', auth: false });
                    return;
                }
                if (xhr.status === 0) {
                    resolve({ success: false, message: 'فشل الاتصال بالخادم' });
                    return;
                }
                if (xhr.status >= 500) {
                    resolve({ success: false, message: 'خطأ في الخادم (' + xhr.status + ')' });
                    return;
                }
                try {
                    var data = JSON.parse(xhr.responseText);
                    resolve(data);
                } catch (e) {
                    console.error('API parse error:', e, 'Status:', xhr.status, 'Response:', xhr.responseText.substring(0, 300));
                    resolve({ success: false, message: 'استجابة غير صالحة من الخادم' });
                }
            }
        };

        xhr.onerror = function() {
            console.error('API network error');
            resolve({ success: false, message: 'خطأ في الاتصال بالخادم' });
        };

        if (options.body) {
            var body = typeof options.body === 'object' ? JSON.stringify(options.body) : options.body;
            xhr.send(body);
        } else {
            xhr.send();
        }
    });
}

// Delete Item - Used by Products & Suppliers
function deleteItem(url, confirmMessage) {
    if (confirm(confirmMessage || 'هل أنت متأكد من الحذف؟')) {
        window.location.href = url;
    }
}

// Add CSS animations
var style = document.createElement('style');
style.textContent =
    '@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }' +
    '@keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }';
document.head.appendChild(style);
