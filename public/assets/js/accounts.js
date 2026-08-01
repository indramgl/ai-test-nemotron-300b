// Accounts JavaScript
let accountsData = [];

document.addEventListener('DOMContentLoaded', async () => {
    await loadAccounts();
    await loadAccountTypes();
    setupAccountForm();
    setupDeleteConfirmation();
});

async function loadAccounts() {
    try {
        const data = await Api.get('/api/accounts');
        accountsData = data.accounts || [];
        renderAccountsGrid(accountsData);
    } catch (err) {
        console.error('Failed to load accounts:', err);
        showToast('Gagal memuat rekening', 'danger');
    }
}

async function loadAccountTypes() {
    try {
        const response = await Api.get('/api/accounts');
        // We'll need a separate endpoint for account types, for now use hardcoded
        const select = document.getElementById('accountTypeId');
        if (select) {
            select.innerHTML = `
                <option value="">Pilih jenis rekening</option>
                <option value="1">Cash</option>
                <option value="2">Bank</option>
                <option value="3">E-Wallet</option>
                <option value="4">Investasi</option>
            `;
        }
    } catch (err) {
        console.error('Failed to load account types:', err);
    }
}

function renderAccountsGrid(accounts) {
    const container = document.getElementById('accountsGrid');
    if (!container) return;

    if (accounts.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-credit-card fs-1 text-muted"></i>
                <p class="mt-2 text-muted">Belum ada rekening. Klik "Tambah Rekening" untuk memulai.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = accounts.map(acc => `
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm account-card" data-id="${acc.id}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-${getAccountTypeColor(acc.account_type_name)} bg-opacity-10 text-${getAccountTypeColor(acc.account_type_name)} rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi ${getAccountTypeIcon(acc.account_type_name)} fs-4"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item edit-account" href="#" data-id="${acc.id}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                <li><a class="dropdown-item text-danger delete-account" href="#" data-id="${acc.id}" data-name="${acc.name}"><i class="bi bi-trash me-2"></i>Hapus</a></li>
                            </ul>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1">${acc.name}</h5>
                    <small class="text-muted">${acc.account_type_name}</small>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-5">${formatCurrency(acc.balance)}</span>
                        <small class="text-muted">${acc.currency}</small>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    // Add event listeners for edit/delete
    document.querySelectorAll('.edit-account').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openEditAccountModal(btn.dataset.id);
        });
    });

    document.querySelectorAll('.delete-account').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const modal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
            document.getElementById('deleteAccountName').textContent = btn.dataset.name;
            document.getElementById('confirmDeleteAccount').dataset.id = btn.dataset.id;
            modal.show();
        });
    });
}

function setupAccountForm() {
    const form = document.getElementById('accountForm');
    const modal = document.getElementById('accountModal');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('accountSubmitBtn');
        const spinner = btn.querySelector('.spinner-border');
        const submitText = document.getElementById('accountSubmitText');

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        data.account_type_id = parseInt(data.account_type_id);
        data.initial_balance = parseFloat(data.initial_balance) || 0;

        const isEdit = data.id && data.id !== '';
        delete data.id;

        btn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const url = isEdit ? `/api/accounts/${form.dataset.editId}` : '/api/accounts';
            const method = isEdit ? 'put' : 'post';

            await Api[method](url, data);

            showToast(isEdit ? 'Rekening diperbarui' : 'Rekening ditambahkan', 'success');
            bootstrap.Modal.getInstance(modal).hide();
            form.reset();
            delete form.dataset.editId;
            submitText.textContent = 'Simpan';
            await loadAccounts();
        } catch (err) {
            showToast(err.message, 'danger');
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    });

    // Reset form when modal closes
    modal.addEventListener('hidden.bs.modal', () => {
        form.reset();
        delete form.dataset.editId;
        document.getElementById('accountModalTitle').textContent = 'Tambah Rekening';
        document.getElementById('accountSubmitText').textContent = 'Simpan';
    });
}

function openEditAccountModal(id) {
    const account = accountsData.find(a => a.id === id);
    if (!account) return;

    const form = document.getElementById('accountForm');
    form.dataset.editId = id;

    document.getElementById('accountModalTitle').textContent = 'Edit Rekening';
    document.getElementById('accountSubmitText').textContent = 'Perbarui';
    document.getElementById('accountTypeId').value = account.account_type_id;
    document.getElementById('accountName').value = account.name;
    document.getElementById('initialBalance').value = account.balance;
    document.getElementById('accountCurrency').value = account.currency;

    const modal = new bootstrap.Modal(document.getElementById('accountModal'));
    modal.show();
}

function setupDeleteConfirmation() {
    const confirmBtn = document.getElementById('confirmDeleteAccount');
    confirmBtn.addEventListener('click', async () => {
        const id = confirmBtn.dataset.id;
        if (!id) return;

        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menghapus...';

        try {
            await Api.delete(`/api/accounts/${id}`);
            showToast('Rekening dihapus', 'success');
            bootstrap.Modal.getInstance(document.getElementById('deleteAccountModal')).hide();
            await loadAccounts();
        } catch (err) {
            showToast(err.message, 'danger');
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = 'Hapus';
        }
    });
}

function getAccountTypeColor(type) {
    const colors = { 'Cash': 'success', 'Bank': 'primary', 'E-Wallet': 'warning', 'Investasi': 'danger' };
    return colors[type] || 'secondary';
}

function getAccountTypeIcon(type) {
    const icons = { 'Cash': 'bi-wallet2', 'Bank': 'bi-bank', 'E-Wallet': 'bi-credit-card', 'Investasi': 'bi-graph-up' };
    return icons[type] || 'bi-wallet';
}