function showToast(message, type = 'info') {
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="toast-header bg-${type} text-white">
                <strong class="me-auto">提示</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    container.innerHTML += toastHtml;
    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
    
    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function formatMoney(amount) {
    return parseFloat(amount).toLocaleString('zh-CN', {
        style: 'currency',
        currency: 'CNY'
    });
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('zh-CN');
}

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

function ajaxPost(url, data, callback) {
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (callback) callback(response);
        },
        error: function(xhr, status, error) {
            showToast('请求失败: ' + error, 'danger');
        }
    });
}

function refreshCaptcha(imgId) {
    const img = document.getElementById(imgId);
    if (img) {
        img.src = img.src.split('?')[0] + '?t=' + Date.now();
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast('已复制到剪贴板', 'success');
    }, function() {
        showToast('复制失败', 'danger');
    });
}

function exportTable(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            let text = col.innerText.replace(/"/g, '""');
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '.csv';
    link.click();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function searchFilter(data, searchTerm) {
    if (!searchTerm) return data;
    searchTerm = searchTerm.toLowerCase();
    return data.filter(item => {
        return Object.values(item).some(value => 
            String(value).toLowerCase().includes(searchTerm)
        );
    });
}

$(document).ready(function() {
    $('.toast').toast({ delay: 3000 });
    
    $('input[required], select[required]').on('blur', function() {
        if (!this.value.trim()) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    $('input[data-money]').on('input', function() {
        let value = this.value.replace(/[^\d.]/g, '');
        value = value.replace(/\.{2,}/g, '.');
        value = value.replace(/^\./g, '0.');
        value = value.replace(/^\d+\.\d{2,}$/g, value.substring(0, value.indexOf('.') + 3));
        this.value = value;
    });
    
    $('.sidebar-nav a.nav-item').on('click', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (href && href !== '#' && !href.startsWith('javascript:')) {
            history.scrollRestoration = 'manual';
            sessionStorage.setItem('scrollPosition', '0');
            window.location.href = href;
        }
    });
});

window.addEventListener('beforeunload', function() {
    history.scrollRestoration = 'manual';
});

window.addEventListener('load', function() {
    const savedPosition = sessionStorage.getItem('scrollPosition');
    if (savedPosition !== null) {
        sessionStorage.removeItem('scrollPosition');
        setTimeout(function() {
            window.scrollTo(0, parseInt(savedPosition) || 0);
        }, 0);
    } else if (window.location.hash === '') {
        window.scrollTo(0, 0);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    history.scrollRestoration = 'manual';
});
