// Goals JavaScript
let goalsData = [];

document.addEventListener('DOMContentLoaded', async () => {
    await loadGoals();
    setupGoalForm();
    setupGoalTransactionForm();
});

async function loadGoals() {
    try {
        const data = await Api.get('/api/goals');
        goalsData = data.goals || [];
        renderGoals(goalsData);
    } catch (err) {
        console.error('Failed to load goals:', err);
        showToast('Gagal memuat target', 'danger');
    }
}

function renderGoals(goals) {
    const container = document.getElementById('goalsGrid');
    if (!container) return;

    if (goals.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-bullseye fs-1 text-muted"></i>
                <p class="mt-2 text-muted">Belum ada target keuangan. Klik "Tambah Target" untuk memulai.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = goals.map(goal => {
        const g = goal.goal || goal;
        const targetAmount = parseFloat(g.target_amount) || 0;
        const currentAmount = parseFloat(g.current_amount) || 0;
        const percentage = targetAmount > 0 ? (currentAmount / targetAmount) * 100 : 0;
        const isCompleted = currentAmount >= targetAmount;
        const daysRemaining = goal.days_remaining !== null ? goal.days_remaining : null;

        let progressClass = 'bg-primary';
        if (isCompleted) progressClass = 'bg-success';
        else if (percentage >= 90) progressClass = 'bg-warning';
        else if (percentage >= 75) progressClass = 'bg-info';

        const colorStyle = g.color ? `style="background-color: ${g.color}20; color: ${g.color}"` : '';

        return `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm goal-card ${isCompleted ? 'border-success' : ''}" data-id="${g.id}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <div class="${colorStyle} rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                                    <i class="bi ${g.icon || 'bi-bullseye'} fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0">${g.name}</h6>
                                    ${g.target_date ? `<small class="text-muted">Target: ${formatDate(g.target_date)}</small>` : ''}
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item view-goal" href="#" data-id="${g.id}"><i class="bi bi-eye me-2"></i>Detail</a></li>
                                    <li><a class="dropdown-item edit-goal" href="#" data-id="${g.id}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-success deposit-goal" href="#" data-id="${g.id}"><i class="bi bi-plus-circle me-2"></i>Setor</a></li>
                                    ${!isCompleted ? `<li><a class="dropdown-item text-warning withdraw-goal" href="#" data-id="${g.id}"><i class="bi bi-dash-circle me-2"></i>Tarik</a></li>` : ''}
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger delete-goal" href="#" data-id="${g.id}" data-name="${g.name}"><i class="bi bi-trash me-2"></i>Hapus</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Progress</span>
                                <span class="fw-semibold">${formatCurrency(currentAmount)} / ${formatCurrency(targetAmount)}</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar ${progressClass} ${isCompleted ? 'bg-success' : ''}" role="progressbar" style="width: ${Math.min(percentage, 100)}%" aria-valuenow="${percentage.toFixed(1)}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span>${percentage.toFixed(1)}% selesai</span>
                                ${daysRemaining !== null ? `<span>${daysRemaining > 0 ? `${daysRemaining} hari lagi` : daysRemaining === 0 ? 'Hari ini' : `${Math.abs(daysRemaining)} hari lewat`}</span>` : ''}
                            </div>
                        </div>
                        
                        ${g.description ? `<p class="small text-muted mb-0">${g.description}</p>` : ''}
                        
                        ${!isCompleted ? `
                        <div class="mt-3 d-grid gap-2">
                            <button class="btn btn-sm btn-outline-primary deposit-goal" data-id="${g.id}">
                                <i class="bi bi-plus-circle me-1"></i>Setor Dana
                            </button>
                        </div>` : `
                        <div class="mt-3">
                            <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Target Tercapai!</span>
                        </div>`}
                    </div>
                </div>
            </div>
        `;
    }).join('');

    // Event listeners
    document.querySelectorAll('.view-goal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openGoalDetailModal(btn.dataset.id);
        });
    });

    document.querySelectorAll('.edit-goal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openEditGoalModal(btn.dataset.id);
        });
    });

    document.querySelectorAll('.deposit-goal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openGoalTransactionModal(btn.dataset.id, 'DEPOSIT');
        });
    });

    document.querySelectorAll('.withdraw-goal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openGoalTransactionModal(btn.dataset.id, 'WITHDRAWAL');
        });
    });

    document.querySelectorAll('.delete-goal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            document.getElementById('confirmModalTitle').textContent = 'Hapus Target';
            document.getElementById('confirmModalBody').textContent = `Hapus target "${btn.dataset.name}"?`;
            document.getElementById('confirmModalAction').dataset.id = btn.dataset.id;
            document.getElementById('confirmModalAction').onclick = () => deleteGoal(btn.dataset.id);
            modal.show();
        });
    });
}

function setupGoalForm() {
    const form = document.getElementById('goalForm');
    const modal = document.getElementById('goalModal');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('goalSubmitBtn');
        const spinner = btn.querySelector('.spinner-border');
        const submitText = document.getElementById('goalSubmitText');

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        data.target_amount = parseFloat(data.target_amount);

        const isEdit = data.id && data.id !== '';
        delete data.id;

        btn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const url = isEdit ? `/api/goals/${form.dataset.editId}` : '/api/goals';
            const method = isEdit ? 'put' : 'post';

            await Api[method](url, data);

            showToast(isEdit ? 'Target diperbarui' : 'Target ditambahkan', 'success');
            bootstrap.Modal.getInstance(modal).hide();
            form.reset();
            delete form.dataset.editId;
            submitText.textContent = 'Simpan';
            document.getElementById('goalModalTitle').textContent = 'Tambah Target';
            await loadGoals();
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
        document.getElementById('goalModalTitle').textContent = 'Tambah Target';
        document.getElementById('goalSubmitText').textContent = 'Simpan';
    });
}

function setupGoalTransactionForm() {
    const form = document.getElementById('goalTransactionForm');
    const modal = document.getElementById('goalTransactionModal');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('goalTransactionSubmitBtn');
        const spinner = btn.querySelector('.spinner-border');

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        data.amount = parseFloat(data.amount);
        data.goal_id = data.goal_id;
        const type = data.type;
        delete data.type;
        delete data.goal_id;

        btn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const url = `/api/goals/${data.goal_id}/${type.toLowerCase()}`;
            await Api.post(url, data);

            showToast(type === 'DEPOSIT' ? 'Dana disetor' : 'Dana ditarik', 'success');
            bootstrap.Modal.getInstance(modal).hide();
            form.reset();
            await loadGoals();
        } catch (err) {
            showToast(err.message, 'danger');
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    });

    modal.addEventListener('hidden.bs.modal', () => {
        form.reset();
    });
}

async function openEditGoalModal(id) {
    const goal = goalsData.find(g => (g.goal || g).id === id);
    if (!goal) return;
    const g = goal.goal || goal;

    const form = document.getElementById('goalForm');
    form.dataset.editId = id;

    document.getElementById('goalModalTitle').textContent = 'Edit Target';
    document.getElementById('goalSubmitText').textContent = 'Perbarui';
    document.getElementById('goalName').value = g.name;
    document.getElementById('goalTargetAmount').value = g.target_amount;
    document.getElementById('goalTargetDate').value = g.target_date || '';
    document.getElementById('goalIcon').value = g.icon || 'bi-bullseye';
    document.getElementById('goalColor').value = g.color || '#0d6efd';
    document.getElementById('goalDescription').value = g.description || '';

    const modal = new bootstrap.Modal(document.getElementById('goalModal'));
    modal.show();
}

async function openGoalDetailModal(id) {
    try {
        const data = await Api.get(`/api/goals/${id}`);
        const goal = data.goal;
        const transactions = data.transactions || [];

        if (!goal) return;

        const targetAmount = parseFloat(goal.target_amount) || 0;
        const currentAmount = parseFloat(goal.current_amount) || 0;
        const percentage = targetAmount > 0 ? (currentAmount / targetAmount) * 100 : 0;
        const isCompleted = currentAmount >= targetAmount;
        const daysRemaining = goal.days_remaining !== null ? goal.days_remaining : null;
        const monthlyNeeded = goal.monthly_needed;

        let progressClass = 'bg-primary';
        if (isCompleted) progressClass = 'bg-success';
        else if (percentage >= 90) progressClass = 'bg-warning';

        const colorStyle = goal.color ? `style="background-color: ${goal.color}20; color: ${goal.color}"` : '';

        document.getElementById('goalDetailTitle').textContent = goal.name;
        document.getElementById('goalDetailBody').innerHTML = `
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="d-flex align-items-center">
                        <div class="${colorStyle} rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px;">
                            <i class="bi ${goal.icon || 'bi-bullseye'} fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">${goal.name}</h5>
                            ${goal.target_date ? `<small class="text-muted">Target: ${formatDate(goal.target_date)}</small>` : ''}
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar ${progressClass}" role="progressbar" style="width: ${Math.min(percentage, 100)}%" aria-valuenow="${percentage.toFixed(1)}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span>${percentage.toFixed(1)}% selesai</span>
                        <span>${formatCurrency(currentAmount)} / ${formatCurrency(targetAmount)}</span>
                    </div>
                </div>
                ${daysRemaining !== null ? `
                <div class="col-6">
                    <strong>Waktu Tersisa</strong><br>
                    <span class="${daysRemaining > 0 ? 'text-primary' : daysRemaining === 0 ? 'text-warning' : 'text-danger'}">
                        ${daysRemaining > 0 ? `${daysRemaining} hari` : daysRemaining === 0 ? 'Hari ini' : `${Math.abs(daysRemaining)} hari lewat`}
                    </span>
                </div>` : ''}
                ${monthlyNeeded ? `
                <div class="col-6">
                    <strong>Butuh Per Bulan</strong><br>
                    <span class="text-info">${formatCurrency(monthlyNeeded)}</span>
                </div>` : ''}
                ${goal.description ? `<div class="col-12"><strong>Deskripsi</strong><br>${goal.description}</div>` : ''}
            </div>
            <hr>
            <h6 class="fw-bold mb-3">Riwayat Transaksi</h6>
            <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                ${transactions.length > 0 ? transactions.map(txn => `
                    <div class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-${txn.type === 'DEPOSIT' ? 'success' : 'warning'} me-2">${txn.type === 'DEPOSIT' ? 'Setor' : 'Tarik'}</span>
                                <span class="fw-semibold">${formatCurrency(txn.amount)}</span>
                            </div>
                            <small class="text-muted">${formatDate(txn.transaction_date)}</small>
                        </div>
                        ${txn.description ? `<small class="text-muted">${txn.description}</small>` : ''}
                    </div>
                `).join('') : '<div class="list-group-item px-0 py-3 text-center text-muted small">Belum ada transaksi</div>'}
            </div>
        `;

        const modal = new bootstrap.Modal(document.getElementById('goalDetailModal'));
        modal.show();
    } catch (err) {
        console.error('Failed to load goal detail:', err);
        showToast('Gagal memuat detail target', 'danger');
    }
}

async function openGoalTransactionModal(goalId, type) {
    const goal = goalsData.find(g => (g.goal || g).id === goalId);
    if (!goal) return;
    const g = goal.goal || goal;

    const form = document.getElementById('goalTransactionForm');
    form.goal_id = goalId;

    document.getElementById('goalTransactionModalTitle').textContent = type === 'DEPOSIT' ? 'Setor ke Target' : 'Tarik dari Target';
    document.getElementById('goalTxnType').value = type;
    document.getElementById('goalTxnAmount').value = '';
    document.getElementById('goalTxnDescription').value = '';
    document.getElementById('goalTxnDate').value = new Date().toISOString().split('T')[0];

    // Update max text for withdrawal
    const maxText = document.getElementById('goalTxnMaxText');
    if (type === 'WITHDRAWAL') {
        const currentAmount = parseFloat(g.current_amount) || 0;
        maxText.textContent = `Maksimal: ${formatCurrency(currentAmount)}`;
    } else {
        const targetAmount = parseFloat(g.target_amount) || 0;
        const currentAmount = parseFloat(g.current_amount) || 0;
        const remaining = targetAmount - currentAmount;
        maxText.textContent = `Sisa ke target: ${formatCurrency(remaining)}`;
    }

    const modal = new bootstrap.Modal(document.getElementById('goalTransactionModal'));
    modal.show();
}

async function deleteGoal(id) {
    try {
        await Api.delete(`/api/goals/${id}`);
        showToast('Target dihapus', 'success');
        bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
        await loadGoals();
    } catch (err) {
        showToast(err.message, 'danger');
    }
}