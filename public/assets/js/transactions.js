// Transactions JavaScript
let transactionsData = [];
let currentPage = 1;
const pageSize = 20;
let accountsList = [];
let categoriesList = [];

document.addEventListener('DOMContentLoaded', async () => {
    await loadInitialData();
    setupTransactionForm();
    setupFilters();
    await loadTransactions();
});

async function loadInitialData() {
    try {
        // Load accounts and categories for dropdowns
        const [accountsRes, categoriesRes] = await Promise.all([
            Api.get('/api/accounts'),
            Api.get('/api/transactions') // This returns categories in the response
        ]);

        accountsList = accountsRes.accounts || [];
        categoriesList = categoriesRes.categories || [];

        populateAccountSelect('txnAccountId', accountsList);
        populateAccountSelect('txnToAccountId', accountsList);
        populateAccountSelect('filterAccount', accountsList, true);
        populateCategorySelect('txnCategoryId', categoriesList);
        populateCategorySelect('filterCategory', categoriesList, true);

        // Set default date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('txnDate').value = today;
        document.getElementById('filterStartDate').value = today;
        document.getElementById('filterEndDate').value = today;
    } catch (err) {
        console.error('Failed to load initial data:', err);
    }
}

function populateAccountSelect(selectId, accounts, includeAll = false) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const currentValue = select.value;
    select.innerHTML = `<option value="">${includeAll ? 'Semua Rekening' : 'Pilih rekening'}</option>`;
    accounts.forEach(acc => {
        const option = document.createElement('option');
        option.value = acc.id;
        option.textContent = `${acc.name} (${acc.account_type_name})`;
        select.appendChild(option);
    });
    if (currentValue) select.value = currentValue;
}

function populateCategorySelect(selectId, categories, includeAll = false) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const currentValue = select.value;
    select.innerHTML = `<option value="">${includeAll ? 'Semua Kategori' : 'Pilih kategori'}</option>`;

    // Group by parent
    const parents = categories.filter(c => !c.parent_id);
    const children = categories.filter(c => c.parent_id);

    parents.forEach(parent => {
        const optgroup = document.createElement('optgroup');
        optgroup.label = parent.name;
        
        const parentOption = document.createElement('option');
        parentOption.value = parent.id;
        parentOption.textContent = parent.name;
        optgroup.appendChild(parentOption);

        children.filter(c => c.parent_id === parent.id).forEach(child => {
            const option = document.createElement('option');
            option.value = child.id;
            option.textContent = `  └ ${child.name}`;
            optgroup.appendChild(option);
        });

        select.appendChild(optgroup);
    });

    if (currentValue) select.value = currentValue;
}

async function loadTransactions(page = 1) {
    currentPage = page;
    const container = document.getElementById('transactionsBody');
    const paginationContainer = document.getElementById('paginationContainer');

    if (container) {
        container.innerHTML = `
            <tr><td colspan="7" class="text-center py-4 text-muted">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 mb-0 small">Memuat...</p>
            </td></tr>
        `;
    }

    try {
        const params = new URLSearchParams({
            limit: pageSize,
            offset: (page - 1) * pageSize
        });

        const filters = getFilters();
        Object.entries(filters).forEach(([key, value]) => {
            if (value) params.append(key, value);
        });

        const data = await Api.get(`/api/transactions?${params.toString()}`);
        transactionsData = data.transactions || [];
        renderTransactions(transactionsData);
        renderPagination(data.total || transactionsData.length, page);
        paginationContainer.style.display = 'flex';
    } catch (err) {
        console.error('Failed to load transactions:', err);
        showToast('Gagal memuat transaksi', 'danger');
        if (container) {
            container.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Gagal memuat data</td></tr>`;
        }
    }
}

function getFilters() {
    return {
        account_id: document.getElementById('filterAccount').value,
        category_id: document.getElementById('filterCategory').value,
        type: document.getElementById('filterType').value,
        start_date: document.getElementById('filterStartDate').value,
        end_date: document.getElementById('filterEndDate').value
    };
}

function renderTransactions(transactions) {
    const container = document.getElementById('transactionsBody');
    if (!container) return;

    if (transactions.length === 0) {
        container.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada transaksi</td></tr>`;
        return;
    }

    container.innerHTML = transactions.map(txn => `
        <tr>
            <td>${formatDate(txn.transaction_date)}</td>
            <td><span class="badge bg-${getTransactionTypeColor(txn.type)}">${getTransactionTypeLabel(txn.type)}</span></td>
            <td>${txn.category_name || '-'}</td>
            <td>${txn.account_name || '-'}</td>
            <td>${txn.description || '-'}</td>
            <td class="text-end fw-bold text-${txn.type === 'INCOME' ? 'success' : txn.type === 'EXPENSE' ? 'danger' : 'warning'}">
                ${txn.type === 'INCOME' ? '+' : txn.type === 'EXPENSE' ? '-' : ''}${formatCurrency(txn.amount)}
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary edit-txn" data-id="${txn.id}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger delete-txn" data-id="${txn.id}" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    // Add event listeners
    document.querySelectorAll('.edit-txn').forEach(btn => {
        btn.addEventListener('click', () => openEditTransactionModal(btn.dataset.id));
    });

    document.querySelectorAll('.delete-txn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (confirm('Hapus transaksi ini?')) {
                deleteTransaction(btn.dataset.id);
            }
        });
    });
}

function renderPagination(total, currentPage) {
    const totalPages = Math.ceil(total / pageSize);
    const infoEl = document.getElementById('paginationInfo');
    const paginationEl = document.getElementById('pagination');

    if (infoEl) {
        const start = (currentPage - 1) * pageSize + 1;
        const end = Math.min(currentPage * pageSize, total);
        infoEl.textContent = `Menampilkan ${start}-${end} dari ${total}`;
    }

    if (paginationEl) {
        let html = '';
        
        // Previous
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}">Sebelumnya</a>
        </li>`;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Next
        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}">Selanjutnya</a>
        </li>`;

        paginationEl.innerHTML = html;

        paginationEl.querySelectorAll('a.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.dataset.page);
                if (page && page !== currentPage) {
                    loadTransactions(page);
                }
            });
        });
    }
}

function setupFilters() {
    const filterIds = ['filterAccount', 'filterCategory', 'filterType', 'filterStartDate', 'filterEndDate'];
    filterIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => loadTransactions(1));
        }
    });
}

function setupTransactionForm() {
    const form = document.getElementById('transactionForm');
    const modal = document.getElementById('transactionModal');
    const typeInput = document.getElementById('transactionType');

    // Handle modal opening with type
    modal.addEventListener('show.bs.modal', (e) => {
        const trigger = e.relatedTarget;
        if (trigger) {
            const type = trigger.dataset.type || 'EXPENSE';
            typeInput.value = type;
            updateFormForType(type);
            document.getElementById('transactionModalTitle').textContent = 
                type === 'INCOME' ? 'Tambah Pemasukan' : type === 'EXPENSE' ? 'Tambah Pengeluaran' : 'Tambah Transfer';
        }
    });

    // Handle recurring checkbox
    document.getElementById('txnIsRecurring').addEventListener('change', (e) => {
        document.getElementById('recurringFields').classList.toggle('d-none', !e.target.checked);
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('transactionSubmitBtn');
        const spinner = btn.querySelector('.spinner-border');

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        data.amount = parseFloat(data.amount);
        data.is_recurring = data.is_recurring === 'on';
        data.recurrence_pattern = data.recurrence_pattern || null;
        data.recurrence_end_date = data.recurrence_end_date || null;

        const isEdit = data.id && data.id !== '';
        delete data.id;

        btn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const url = isEdit ? `/api/transactions/${form.dataset.editId}` : '/api/transactions';
            const method = isEdit ? 'put' : 'post';

            await Api[method](url, data);

            showToast(isEdit ? 'Transaksi diperbarui' : 'Transaksi ditambahkan', 'success');
            bootstrap.Modal.getInstance(modal).hide();
            form.reset();
            delete form.dataset.editId;
            await loadTransactions(currentPage);
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
        document.getElementById('txnIsRecurring').checked = false;
        document.getElementById('recurringFields').classList.add('d-none');
        document.getElementById('transferFields').classList.add('d-none');
        document.getElementById('transactionModalTitle').textContent = 'Tambah Transaksi';
    });
}

function updateFormForType(type) {
    const transferFields = document.getElementById('transferFields');
    const categorySelect = document.getElementById('txnCategoryId');

    if (type === 'TRANSFER') {
        transferFields.classList.remove('d-none');
        // Filter categories to only show Transfer type
        filterCategoriesByType(categorySelect, 'TRANSFER');
    } else {
        transferFields.classList.add('d-none');
        // Filter categories by type
        filterCategoriesByType(categorySelect, type);
    }
}

// Filter categories by type (INCOME, EXPENSE, TRANSFER)
function filterCategoriesByType(selectElement, type) {
    if (!selectElement) return;
    
    const currentValue = selectElement.value;
    selectElement.innerHTML = `<option value="">Pilih kategori</option>`;

    // Group by parent
    const parents = categoriesList.filter(c => !c.parent_id && c.type === type);
    const children = categoriesList.filter(c => c.parent_id && c.type === type);

    parents.forEach(parent => {
        const optgroup = document.createElement('optgroup');
        optgroup.label = parent.name;
        
        const parentOption = document.createElement('option');
        parentOption.value = parent.id;
        parentOption.textContent = parent.name;
        optgroup.appendChild(parentOption);

        children.filter(c => c.parent_id === parent.id).forEach(child => {
            const option = document.createElement('option');
            option.value = child.id;
            option.textContent = `  └ ${child.name}`;
            optgroup.appendChild(option);
        });

        selectElement.appendChild(optgroup);
    });

    if (currentValue) selectElement.value = currentValue;
}

async function openEditTransactionModal(id) {
    try {
        const data = await Api.get(`/api/transactions/${id}`);
        const txn = data.transaction;

        if (!txn) return;

        const form = document.getElementById('transactionForm');
        form.dataset.editId = id;

        document.getElementById('transactionModalTitle').textContent = 'Edit Transaksi';
        document.getElementById('transactionType').value = txn.type;
        document.getElementById('txnAccountId').value = txn.account_id;
        document.getElementById('txnCategoryId').value = txn.category_id;
        document.getElementById('txnAmount').value = txn.amount;
        document.getElementById('txnDate').value = txn.transaction_date;
        document.getElementById('txnDescription').value = txn.description || '';
        document.getElementById('txnIsRecurring').checked = txn.is_recurring;
        
        if (txn.is_recurring) {
            document.getElementById('recurringFields').classList.remove('d-none');
            document.getElementById('txnRecurrencePattern').value = txn.recurrence_pattern;
            document.getElementById('txnRecurrenceEndDate').value = txn.recurrence_end_date || '';
        }

        if (txn.type === 'TRANSFER') {
            document.getElementById('transferFields').classList.remove('d-none');
            // We'd need to get the destination account from somewhere
        }

        const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
        modal.show();
    } catch (err) {
        console.error('Failed to load transaction:', err);
        showToast('Gagal memuat transaksi', 'danger');
    }
}

async function deleteTransaction(id) {
    try {
        await Api.delete(`/api/transactions/${id}`);
        showToast('Transaksi dihapus', 'success');
        await loadTransactions(currentPage);
    } catch (err) {
        showToast(err.message, 'danger');
    }
}

function getTransactionTypeColor(type) {
    const colors = { 'INCOME': 'success', 'EXPENSE': 'danger', 'TRANSFER': 'warning' };
    return colors[type] || 'secondary';
}

function getTransactionTypeLabel(type) {
    const labels = { 'INCOME': 'Masuk', 'EXPENSE': 'Keluar', 'TRANSFER': 'Transfer' };
    return labels[type] || type;
}