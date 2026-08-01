// Dashboard JavaScript
let cashflowChart = null;
let expenseChart = null;

document.addEventListener('DOMContentLoaded', async () => {
    await loadDashboardData();
    initializeCharts();
});

async function loadDashboardData() {
    try {
        const data = await Api.get('/api/dashboard');
        
        // Update stats cards
        updateStatCard('totalBalance', data.total_balance);
        updateStatCard('monthlyIncome', data.monthly_income, 'success');
        updateStatCard('monthlyExpense', data.monthly_expense, 'danger');
        updateStatCard('monthlyNet', data.monthly_net);
        
        // Update accounts list
        renderAccounts(data.accounts);
        
        // Update recent transactions
        renderRecentTransactions(data.recent_transactions);
        
        // Update budget alerts
        renderBudgetAlerts(data.budget_alerts);
        
        // Update charts
        updateCharts(data);
        
    } catch (err) {
        console.error('Failed to load dashboard:', err);
        showToast('Gagal memuat dashboard', 'danger');
    }
}

function updateStatCard(elementId, amount, colorClass = '') {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = formatCurrency(amount);
        if (colorClass) {
            el.className = `fw-bold text-${colorClass} mb-0`;
        } else if (amount < 0) {
            el.className = 'fw-bold text-danger mb-0';
        } else {
            el.className = 'fw-bold text-success mb-0';
        }
    }
}

function renderAccounts(accounts) {
    const container = document.getElementById('accountsList');
    if (!container) return;

    if (!accounts || accounts.length === 0) {
        container.innerHTML = `
            <div class="list-group-item text-center py-4 text-muted">
                <i class="bi bi-credit-card fs-1"></i>
                <p class="mt-2 mb-0 small">Belum ada rekening. <a href="/accounts" class="text-decoration-none">Tambahkan</a></p>
            </div>
        `;
        return;
    }

    container.innerHTML = accounts.map(acc => `
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="bg-${getAccountTypeColor(acc.account_type_name)} bg-opacity-10 text-${getAccountTypeColor(acc.account_type_name)} rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <i class="bi ${getAccountTypeIcon(acc.account_type_name)}"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-semibold">${acc.name}</h6>
                    <small class="text-muted">${acc.account_type_name}</small>
                </div>
            </div>
            <span class="fw-bold">${formatCurrency(acc.balance)}</span>
        </div>
    `).join('');
}

function renderRecentTransactions(transactions) {
    const container = document.getElementById('recentTransactions');
    if (!container) return;

    if (!transactions || transactions.length === 0) {
        container.innerHTML = `
            <div class="list-group-item text-center py-4 text-muted">
                <i class="bi bi-cash-stack fs-1"></i>
                <p class="mt-2 mb-0 small">Belum ada transaksi</p>
            </div>
        `;
        return;
    }

    container.innerHTML = transactions.slice(0, 10).map(txn => `
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1 me-3">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-${getTransactionTypeColor(txn.type)} me-2">${getTransactionTypeLabel(txn.type)}</span>
                        <span class="fw-semibold">${txn.category_name || 'Tanpa kategori'}</span>
                    </div>
                    <small class="text-muted">${txn.account_name} • ${formatDate(txn.transaction_date)}</small>
                </div>
                <span class="fw-bold text-${txn.type === 'INCOME' ? 'success' : txn.type === 'EXPENSE' ? 'danger' : 'warning'}">
                    ${txn.type === 'INCOME' ? '+' : txn.type === 'EXPENSE' ? '-' : ''}${formatCurrency(txn.amount)}
                </span>
            </div>
            ${txn.description ? `<small class="text-muted">${txn.description}</small>` : ''}
        </div>
    `).join('');
}

function renderBudgetAlerts(alerts) {
    const container = document.getElementById('budgetAlertsContainer');
    const textEl = document.getElementById('budgetAlertText');
    
    if (!container || !textEl) return;

    if (alerts && alerts.length > 0) {
        const alert = alerts[0];
        textEl.textContent = alert.message;
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

function initializeCharts() {
    // Cashflow Chart
    const cashflowCtx = document.getElementById('cashflowChart');
    if (cashflowCtx) {
        cashflowChart = new Chart(cashflowCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: [],
                        backgroundColor: 'rgba(25, 135, 84, 0.8)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Pengeluaran',
                        data: [],
                        backgroundColor: 'rgba(220, 53, 69, 0.8)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: ${formatCurrency(ctx.raw)}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => formatCurrency(value)
                        }
                    }
                }
            }
        });
    }

    // Expense Chart
    const expenseCtx = document.getElementById('expenseChart');
    if (expenseCtx) {
        expenseChart = new Chart(expenseCtx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.8)',
                        'rgba(25, 135, 84, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(111, 66, 193, 0.8)',
                        'rgba(253, 126, 20, 0.8)',
                        'rgba(22, 163, 74, 0.8)',
                        'rgba(132, 136, 141, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.label}: ${formatCurrency(ctx.raw)} (${ctx.parsed}%)`
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
}

function updateCharts(data) {
    // Load cashflow data from reports API
    loadCashflowChart();
    loadExpenseChart();
}

async function loadCashflowChart() {
    if (!cashflowChart) return;
    
    try {
        const response = await Api.get('/api/reports/cashflow?months=6');
        const cashflow = response.cashflow || [];
        
        cashflowChart.data.labels = cashflow.map(c => {
            const [y, m] = c.month.split('-');
            return new Date(y, m - 1).toLocaleDateString('id-ID', { month: 'short', year: '2-digit' });
        });
        cashflowChart.data.datasets[0].data = cashflow.map(c => c.income);
        cashflowChart.data.datasets[1].data = cashflow.map(c => c.expense);
        cashflowChart.update();
    } catch (err) {
        console.error('Failed to load cashflow chart:', err);
    }
}

async function loadExpenseChart() {
    if (!expenseChart) return;
    
    try {
        const response = await Api.get('/api/reports/summary');
        const summary = response.category_summary || [];
        const expenses = summary.filter(s => s.category_type === 'EXPENSE').slice(0, 8);
        
        expenseChart.data.labels = expenses.map(e => e.category_name);
        expenseChart.data.datasets[0].data = expenses.map(e => e.total_amount);
        expenseChart.update();
    } catch (err) {
        console.error('Failed to load expense chart:', err);
    }
}

function getAccountTypeColor(type) {
    const colors = {
        'Cash': 'success',
        'Bank': 'primary',
        'E-Wallet': 'warning',
        'Investasi': 'danger'
    };
    return colors[type] || 'secondary';
}

function getAccountTypeIcon(type) {
    const icons = {
        'Cash': 'bi-wallet2',
        'Bank': 'bi-bank',
        'E-Wallet': 'bi-credit-card',
        'Investasi': 'bi-graph-up'
    };
    return icons[type] || 'bi-wallet';
}

function getTransactionTypeColor(type) {
    const colors = { 'INCOME': 'success', 'EXPENSE': 'danger', 'TRANSFER': 'warning' };
    return colors[type] || 'secondary';
}

function getTransactionTypeLabel(type) {
    const labels = { 'INCOME': 'Masuk', 'EXPENSE': 'Keluar', 'TRANSFER': 'Transfer' };
    return labels[type] || type;
}