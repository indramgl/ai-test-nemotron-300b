// Budgets JavaScript
let budgetsData = [];

document.addEventListener('DOMContentLoaded', async () => {
    await loadBudgets();
    await loadCategories();
    setupBudgetForm();
});

async function loadBudgets() {
    try {
        const data = await Api.get('/api/budgets');
        budgetsData = data.budgets || [];
        renderBudgets(budgetsData);
        renderBudgetAlerts(data.alerts || []);
    } catch (err) {
        console.error('Failed to load budgets:', err);
        showToast('Gagal memuat anggaran', 'danger');
    }
}

async function loadCategories() {
    try {
        const data = await Api.get('/api/budgets');
        const categories = data.categories || [];
        const select = document.getElementById('budgetCategoryId');
        if (select) {
            select.innerHTML = '<option value="">Pilih kategori pengeluaran</option>';
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.name;
                select.appendChild(option);
            });
        }
    } catch (err) {
        console.error('Failed to load categories:', err);
    }
}

function renderBudgets(budgets) {
    const container = document.getElementById('budgetsGrid');
    if (!container) return;

    if (budgets.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-graph-up fs-1 text-muted"></i>
                <p class="mt-2 text-muted">Belum ada anggaran. Klik "Tambah Anggaran" untuk memulai.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = budgets.map(budget => {
        const usage = budget.usage || {};
        const spent = parseFloat(usage.spent_amount) || 0;
        const amount = parseFloat(budget.amount) || 0;
        const percentage = amount > 0 ? (spent / amount) * 100 : 0;
        const isOver = percentage >= 100;
        const isWarning = percentage >= 80 && percentage < 100;

        let progressClass = 'bg-success';
        if (isOver) progressClass = 'bg-danger';
        else if (isWarning) progressClass = 'bg-warning';

        return `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm budget-card" data-id="${budget.id}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h6 class="fw-bold mb-0">${budget.category_name}</h6>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item view-budget" href="#" data-id="${budget.id}"><i class="bi bi-eye me-2"></i>Detail</a></li>
                                    <li><a class="dropdown-item edit-budget" href="#" data-id="${budget.id}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li><a class="dropdown-item text-danger delete-budget" href="#" data-id="${budget.id}" data-name="${budget.category_name}"><i class="bi bi-trash me-2"></i>Hapus</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Terpakai</span>
                                <span class="fw-semibold">${formatCurrency(spent)} / ${formatCurrency(amount)}</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar ${progressClass}" role="progressbar" style="width: ${Math.min(percentage, 100)}%" aria-valuenow="${percentage.toFixed(1)}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted d-block text-end mt-1">${percentage.toFixed(1)}% digunakan</small>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Periode: ${budget.period}</span>
                            <span>${budget.start_date} ${budget.end_date ? '- ' + budget.end_date : ''}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    // Event listeners
    document.querySelectorAll('.view-budget').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openBudgetDetailModal(btn.dataset.id);
        });
    });

    document.querySelectorAll('.edit-budget').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openEditBudgetModal(btn.dataset.id);
        });
    });

    document.querySelectorAll('.delete-budget').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            document.getElementById('confirmModalTitle').textContent = 'Hapus Anggaran';
            document.getElementById('confirmModalBody').textContent = `Hapus anggaran untuk "${btn.dataset.name}"?`;
            document.getElementById('confirmModalAction').dataset.id = btn.dataset.id;
            document.getElementById('confirmModalAction').onclick = () => deleteBudget(btn.dataset.id);
            modal.show();
        });
    });
}

function renderBudgetAlerts(alerts) {
    const container = document.getElementById('budgetAlertsContainer');
    const alertsEl = document.getElementById('budgetAlerts');
    
    if (!container || !alertsEl) return;

    if (alerts.length > 0) {
        alertsEl.innerHTML = alerts.map(alert => `
            <div class="alert alert-${alert.type === 'danger' ? 'danger' : 'warning'} border-0 shadow-sm d-flex align-items-center mb-2" role="alert">
                <i class="bi bi-${alert.type === 'danger' ? 'exclamation-triangle-fill' : 'exclamation-circle-fill'} fs-4 me-3"></i>
                <div class="flex-grow-1">${alert.message}</div>
            </div>
        `).join('');
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

function setupBudgetForm() {
    const form = document.getElementById('budgetForm');
    const modal = document.getElementById('budgetModal');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('budgetSubmitBtn');
        const spinner = btn.querySelector('.spinner-border');
        const submitText = document.getElementById('budgetSubmitText');

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        data.amount = parseFloat(data.amount);
        data.alert_threshold_80 = data.alert_threshold_80 === 'on';
        data.alert_threshold_100 = data.alert_threshold_100 === 'on';

        const isEdit = data.id && data.id !== '';
        delete data.id;

        btn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const url = isEdit ? `/api/budgets/${form.dataset.editId}` : '/api/budgets';
            const method = isEdit ? 'put' : 'post';

            await Api[method](url, data);

            showToast(isEdit ? 'Anggaran diperbarui' : 'Anggaran ditambahkan', 'success');
            bootstrap.Modal.getInstance(modal).hide();
            form.reset();
            delete form.dataset.editId;
            submitText.textContent = 'Simpan';
            document.getElementById('budgetModalTitle').textContent = 'Tambah Anggaran';
            await loadBudgets();
        } catch (err) {
            showToast(err.message, 'danger');
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    });

    modal.addEventListener('hidden.bs.modal', () => {
        form.reset();
        delete form.dataset.editId;
        document.getElementById('budgetModalTitle').textContent = 'Tambah Anggaran';
        document.getElementById('budgetSubmitText').textContent = 'Simpan';
    });
}

async function openEditBudgetModal(id) {
    const budget = budgetsData.find(b => b.id === id);
    if (!budget) return;

    const form = document.getElementById('budgetForm');
    form.dataset.editId = id;

    document.getElementById('budgetModalTitle').textContent = 'Edit Anggaran';
    document.getElementById('budgetSubmitText').textContent = 'Perbarui';
    document.getElementById('budgetCategoryId').value = budget.category_id;
    document.getElementById('budgetAmount').value = budget.amount;
    document.getElementById('budgetPeriod').value = budget.period;
    document.getElementById('budgetStartDate').value = budget.start_date;
    document.getElementById('budgetEndDate').value = budget.end_date || '';
    document.getElementById('budgetAlert80').checked = budget.alert_threshold_80;
    document.getElementById('budgetAlert100').checked = budget.alert_threshold_100;

    const modal = new bootstrap.Modal(document.getElementById('budgetModal'));
    modal.show();
}

async function openBudgetDetailModal(id) {
    try {
        const data = await Api.get(`/api/budgets/${id}`);
        const budget = data.budget;
        const usage = data.usage;

        if (!budget) return;

        const spent = parseFloat(usage?.spent_amount) || 0;
        const amount = parseFloat(budget.amount) || 0;
        const percentage = amount > 0 ? (spent / amount) * 100 : 0;
        const remaining = amount - spent;
        const isOver = percentage >= 100;

        let progressClass = 'bg-success';
        if (isOver) progressClass = 'bg-danger';
        else if (percentage >= 80) progressClass = 'bg-warning';

        document.getElementById('budgetDetailBody').innerHTML = `
            <div class="mb-4">
                <h6 class="fw-bold">${budget.category_name}</h6>
                <div class="d-flex justify-content-between small mb-1">
                    <span>Anggaran</span>
                    <span class="fw-semibold">${formatCurrency(amount)}</span>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                    <span>Terpakai</span>
                    <span class="fw-semibold text-${isOver ? 'danger' : 'primary'}">${formatCurrency(spent)}</span>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                    <span>Sisa</span>
                    <span class="fw-semibold text-${remaining >= 0 ? 'success' : 'danger'}">${formatCurrency(remaining)}</span>
                </div>
                <div class="progress mt-2" style="height: 10px;">
                    <div class="progress-bar ${progressClass}" role="progressbar" style="width: ${Math.min(percentage, 100)}%" aria-valuenow="${percentage.toFixed(1)}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted d-block text-end mt-1">${percentage.toFixed(1)}% digunakan</small>
            </div>
            <div class="row g-3 small">
                <div class="col-6">
                    <strong>Periode</strong><br>${budget.period}
                </div>
                <div class="col-6">
                    <strong>Mulai</strong><br>${budget.start_date}
                </div>
                ${budget.end_date ? `
                <div class="col-6">
                    <strong>Berakhir</strong><br>${budget.end_date}
                </div>` : ''}
                <div class="col-6">
                    <strong>Notifikasi 80%</strong><br>${budget.alert_threshold_80 ? 'Ya' : 'Tidak'}
                </div>
                <div class="col-6">
                    <strong>Notifikasi 100%</strong><br>${budget.alert_threshold_100 ? 'Ya' : 'Tidak'}
                </div>
            </div>
        `;

        const modal = new bootstrap.Modal(document.getElementById('budgetDetailModal'));
        modal.show();
    } catch (err) {
        console.error('Failed to load budget detail:', err);
        showToast('Gagal memuat detail anggaran', 'danger');
    }
}

async function deleteBudget(id) {
    try {
        await Api.delete(`/api/budgets/${id}`);
        showToast('Anggaran dihapus', 'success');
        bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
        await loadBudgets();
    } catch (err) {
        showToast(err.message, 'danger');
    }
}