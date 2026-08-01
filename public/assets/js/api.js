// API Utility Functions
class Api {
    static async request(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            credentials: 'include'
        };

        const response = await fetch(url, { ...defaultOptions, ...options });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.error || `HTTP ${response.status}`);
        }

        return data;
    }

    static get(url) {
        return this.request(url, { method: 'GET' });
    }

    static post(url, body) {
        return this.request(url, { method: 'POST', body: JSON.stringify(body) });
    }

    static put(url, body) {
        return this.request(url, { method: 'PUT', body: JSON.stringify(body) });
    }

    static delete(url) {
        return this.request(url, { method: 'DELETE' });
    }
}

// Toast notifications
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toastId = 'toast-' + Date.now();
    const icons = {
        success: 'bi-check-circle-fill',
        danger: 'bi-exclamation-triangle-fill',
        warning: 'bi-exclamation-circle-fill',
        info: 'bi-info-circle-fill'
    };
    const colors = {
        success: 'bg-success',
        danger: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
    };

    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast ${colors[type] || 'bg-info'} text-white`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="toast-header ${colors[type] || 'bg-info'} text-white border-0">
            <i class="bi ${icons[type] || 'bi-info-circle-fill'} me-2"></i>
            <strong class="me-auto">Notifikasi</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;

    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Format currency
function formatCurrency(amount, currency = 'IDR') {
    const num = parseFloat(amount) || 0;
    if (currency === 'IDR') {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(num);
    }
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
}

// Format date
function formatDate(dateStr) {
    const date = new Date(dateStr + 'T00:00:00');
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(date);
}

// Get current user info
async function getCurrentUser() {
    try {
        const data = await Api.get('/api/dashboard');
        return data;
    } catch (err) {
        return null;
    }
}

// Initialize common elements
document.addEventListener('DOMContentLoaded', async () => {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(el => new bootstrap.Popover(el));

    // Logout handler
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await Api.post('/api/auth/logout');
                window.location.href = '/login';
            } catch (err) {
                showToast('Gagal logout', 'danger');
            }
        });
    }

    // Update user info in navbar
    try {
        const data = await Api.get('/api/dashboard');
        const userName = document.getElementById('userName');
        const userEmail = document.getElementById('userEmail');
        const userDisplayName = document.getElementById('userDisplayName');
        
        if (userName && data.user) {
            const name = data.user.first_name ? `${data.user.first_name} ${data.user.last_name || ''}`.trim() : data.user.email;
            userName.textContent = name;
            userDisplayName.textContent = name.split(' ')[0];
        }
        if (userEmail && data.user) {
            userEmail.textContent = data.user.email;
        }
    } catch (err) {
        // User not logged in or token expired
    }
});

// Modal form handler
async function handleModalForm(formId, apiUrl, successMessage, onSuccess) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = form.querySelector('button[type="submit"]');
        const spinner = btn?.querySelector('.spinner-border');
        const submitText = btn?.querySelector('[id$="SubmitText"]') || btn;
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        // Convert numeric fields
        Object.keys(data).forEach(key => {
            if (key.includes('amount') || key.includes('balance') || key.includes('target_amount')) {
                data[key] = parseFloat(data[key]) || 0;
            }
        });

        if (btn) {
            btn.disabled = true;
            if (spinner) spinner.classList.remove('d-none');
        }

        try {
            const isEdit = data.id && data.id !== '';
            const url = isEdit ? `${apiUrl}/${data.id}` : apiUrl;
            const method = isEdit ? 'put' : 'post';
            
            delete data.id;
            
            await Api[method](url, data);
            
            showToast(successMessage, 'success');
            
            const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
            if (modal) modal.hide();
            
            form.reset();
            
            if (onSuccess) onSuccess();
        } catch (err) {
            showToast(err.message, 'danger');
        } finally {
            if (btn) {
                btn.disabled = false;
                if (spinner) spinner.classList.add('d-none');
            }
        }
    });
}

// Confirm delete handler
function setupDeleteConfirmation(modalId, confirmBtnId, itemNameId, onConfirm) {
    const modal = document.getElementById(modalId);
    const confirmBtn = document.getElementById(confirmBtnId);
    const nameEl = document.getElementById(itemNameId);
    
    if (!modal || !confirmBtn || !nameEl) return;

    let deleteCallback = null;
    
    modal.addEventListener('show.bs.modal', (e) => {
        const trigger = e.relatedTarget;
        if (trigger) {
            nameEl.textContent = trigger.dataset.name || 'item ini';
            deleteCallback = () => onConfirm(trigger.dataset.id);
        }
    });

    confirmBtn.addEventListener('click', () => {
        if (deleteCallback) {
            deleteCallback();
            const bsModal = bootstrap.Modal.getInstance(modal);
            bsModal.hide();
        }
    });
}

// Load select options from API
async function loadSelectOptions(selectId, apiUrl, valueField = 'id', labelField = 'name', defaultOption = 'Pilih...') {
    const select = document.getElementById(selectId);
    if (!select) return;

    try {
        const data = await Api.get(apiUrl);
        const items = Array.isArray(data) ? data : (data.accounts || data.categories || data.budgets || data.goals || []);
        
        select.innerHTML = `<option value="">${defaultOption}</option>`;
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = item[labelField] || item.name || item.category_name;
            if (item.icon) option.textContent = `${item.icon} ${option.textContent}`;
            select.appendChild(option);
        });
    } catch (err) {
        console.error(`Failed to load ${selectId}:`, err);
    }
}