// Reports JavaScript
let cashflowChart = null;
let assetsChart = null;
let expenseChart = null;

document.addEventListener('DOMContentLoaded', async () => {
    await loadReportsData();
    initializeCharts();
});

async function loadReportsData() {
    try {
        // Load all report data in parallel
        const [summaryRes, cashflowRes, networthRes] = await Promise.all([
            Api.get('/api/reports/summary'),
            Api.get('/api/reports/cashflow?months=12'),
            Api.get('/api/reports/networth')
        ]);

        updateSummaryCards(summaryRes, networthRes);
        updateCashflowChart(cashflowRes);
        updateAssetsChart(networthRes);
        updateExpenseChart(summaryRes);
        renderGoalsProgress(networthRes);
        populateMonthSelector(summaryRes);

    } catch (err) {
        console.error('Failed to load reports:', err);
        showToast('Gagal memuat laporan', 'danger');
    }
}

function updateSummaryCards(summary, networth) {
    const totalAssets = networth.total_assets || 0;
    const totalLiabilities = networth.total_liabilities || 0;
    const netWorth = networth.net_worth || 0;
    const goalsProgress = networth.goals?.reduce((sum, g) => sum + (g.current_amount || 0), 0) || 0;

    document.getElementById('reportTotalAssets').textContent = formatCurrency(totalAssets);
    document.getElementById('reportTotalLiabilities').textContent = formatCurrency(totalLiabilities);
    document.getElementById('reportNetWorth').textContent = formatCurrency(netWorth);
    document.getElementById('reportGoalsProgress').textContent = formatCurrency(goalsProgress);
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
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Pengeluaran',
                        data: [],
                        backgroundColor: 'rgba(220, 53, 69, 0.8)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true } },
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

    // Assets Chart (Doughnut)
    const assetsCtx = document.getElementById('assetsChart');
    if (assetsCtx) {
        assetsChart = new Chart(assetsCtx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.85)',
                        'rgba(25, 135, 84, 0.85)',
                        'rgba(255, 193, 7, 0.85)',
                        'rgba(220, 53, 69, 0.85)',
                        'rgba(111, 66, 193, 0.85)',
                        'rgba(253, 126, 20, 0.85)',
                        'rgba(22, 163, 74, 0.85)',
                        'rgba(132, 136, 141, 0.85)'
                    ],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { font: { size: 11 }, usePointStyle: true } },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.label}: ${formatCurrency(ctx.raw)} (${ctx.parsed}%)`
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // Expense Chart
    const expenseCtx = document.getElementById('expenseChart');
    if (expenseCtx) {
        expenseChart = new Chart(expenseCtx, {
            type: 'bar',
            indexAxis: 'y',
            data: {
                labels: [],
                datasets: [{
                    label: 'Pengeluaran',
                    data: [],
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${formatCurrency(ctx.raw)}`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => formatCurrency(value)
                        }
                    }
                }
            }
        });
    }
}

function updateCashflowChart(data) {
    if (!cashflowChart) return;

    const cashflow = data.cashflow || [];
    cashflowChart.data.labels = cashflow.map(c => {
        const [y, m] = c.month.split('-');
        return new Date(y, m - 1).toLocaleDateString('id-ID', { month: 'short', year: '2-digit' });
    });
    cashflowChart.data.datasets[0].data = cashflow.map(c => c.income || 0);
    cashflowChart.data.datasets[1].data = cashflow.map(c => c.expense || 0);
    cashflowChart.update();
}

function updateAssetsChart(data) {
    if (!assetsChart) return;

    const accounts = data.accounts || [];
    const positiveAccounts = accounts.filter(a => parseFloat(a.balance) > 0);
    
    assetsChart.data.labels = positiveAccounts.map(a => a.name);
    assetsChart.data.datasets[0].data = positiveAccounts.map(a => parseFloat(a.balance));
    assetsChart.update();
}

function updateExpenseChart(data) {
    if (!expenseChart) return;

    const summary = data.category_summary || [];
    const expenses = summary.filter(s => s.category_type === 'EXPENSE').slice(0, 10);
    
    expenseChart.data.labels = expenses.map(e => e.category_name);
    expenseChart.data.datasets[0].data = expenses.map(e => e.total_amount || 0);
    expenseChart.update();
}

function renderGoalsProgress(data) {
    const container = document.getElementById('goalsProgressContainer');
    if (!container) return;

    const goals = data.goals || [];
    
    if (goals.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-bullseye fs-1"></i>
                <p class="mt-2 mb-0 small">Belum ada target keuangan</p>
            </div>
        `;
        return;
    }

    container.innerHTML = goals.map(g => {
        const targetAmount = parseFloat(g.target_amount) || 0;
        const currentAmount = parseFloat(g.current_amount) || 0;
        const percentage = targetAmount > 0 ? (currentAmount / targetAmount) * 100 : 0;
        const isCompleted = currentAmount >= targetAmount;

        let progressClass = 'bg-primary';
        if (isCompleted) progressClass = 'bg-success';
        else if (percentage >= 90) progressClass = 'bg-warning';
        else if (percentage >= 75) progressClass = 'bg-info';

        const colorStyle = g.color ? `style="background-color: ${g.color}20; color: ${g.color}"` : '';

        return `
            <div class="mb-3 p-3 border rounded ${isCompleted ? 'border-success bg-success bg-opacity-10' : ''}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center">
                        <div class="${colorStyle} rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                            <i class="bi ${g.icon || 'bi-bullseye'} fs-6"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 small">${g.name}</h6>
                            ${g.target_date ? `<small class="text-muted">Target: ${formatDate(g.target_date)}</small>` : ''}
                        </div>
                    </div>
                    <span class="badge ${isCompleted ? 'bg-success' : 'bg-primary'}">${percentage.toFixed(0)}%</span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar ${progressClass}" role="progressbar" style="width: ${Math.min(percentage, 100)}%"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted mt-1">
                    <span>${formatCurrency(currentAmount)} / ${formatCurrency(targetAmount)}</span>
                    ${g.days_remaining !== null ? `<span>${g.days_remaining > 0 ? `${g.days_remaining} hari` : 'Selesai'}</span>` : ''}
                </div>
            </div>
        `;
    }).join('');
}

function populateMonthSelector(data) {
    const select = document.getElementById('reportExpenseMonth');
    if (!select) return;

    // Get unique months from category summary dates
    // For simplicity, we'll just add last 12 months
    const months = [];
    for (let i = 0; i < 12; i++) {
        const date = new Date();
        date.setMonth(date.getMonth() - i);
        const value = date.toISOString().slice(0, 7); // YYYY-MM
        const label = date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
        months.push({ value, label });
    }

    select.innerHTML = '<option value="">Bulan Ini</option>';
    months.forEach(m => {
        const option = document.createElement('option');
        option.value = m.value;
        option.textContent = m.label;
        select.appendChild(option);
    });

    select.addEventListener('change', async () => {
        if (select.value) {
            await loadMonthlyExpense(select.value);
        } else {
            await loadReportsData();
        }
    });
}

async function loadMonthlyExpense(yearMonth) {
    try {
        // This would need a new API endpoint, for now just reload
        const data = await Api.get(`/api/reports/summary`);
        updateExpenseChart(data);
    } catch (err) {
        console.error('Failed to load monthly expense:', err);
    }
}

// Export functions
document.addEventListener('DOMContentLoaded', () => {
    // Export Transactions CSV
    const exportTransactionsBtn = document.getElementById('exportTransactionsCsv');
    if (exportTransactionsBtn) {
        exportTransactionsBtn.addEventListener('click', async () => {
            exportTransactionsBtn.disabled = true;
            exportTransactionsBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyiapkan...';
            
            try {
                // Get all transactions
                let allTransactions = [];
                let offset = 0;
                const limit = 1000;
                
                while (true) {
                    const data = await Api.get(`/api/transactions?limit=${limit}&offset=${offset}`);
                    const transactions = data.transactions || [];
                    if (transactions.length === 0) break;
                    allTransactions = allTransactions.concat(transactions);
                    if (transactions.length < limit) break;
                    offset += limit;
                }

                // Generate CSV
                const headers = ['Tanggal', 'Tipe', 'Kategori', 'Rekening', 'Jumlah', 'Deskripsi'];
                const rows = allTransactions.map(t => [
                    t.transaction_date,
                    t.type === 'INCOME' ? 'Pemasukan' : t.type === 'EXPENSE' ? 'Pengeluaran' : 'Transfer',
                    t.category_name || '',
                    t.account_name || '',
                    t.type === 'EXPENSE' ? -t.amount : t.amount,
                    t.description || ''
                ]);

                const csvContent = [headers, ...rows].map(r => r.map(v => `"${v}"`).join(',')).join('\n');
                downloadCSV(csvContent, `transaksi-${new Date().toISOString().slice(0,10)}.csv`);
                
                showToast('CSV transaksi diunduh', 'success');
            } catch (err) {
                console.error('Export failed:', err);
                showToast('Gagal ekspor', 'danger');
            } finally {
                exportTransactionsBtn.disabled = false;
                exportTransactionsBtn.innerHTML = '<i class="bi bi-download me-1"></i>Unduh CSV';
            }
        });
    }

    // Export Monthly CSV
    const exportMonthlyBtn = document.getElementById('exportMonthlyCsv');
    if (exportMonthlyBtn) {
        exportMonthlyBtn.addEventListener('click', async () => {
            exportMonthlyBtn.disabled = true;
            exportMonthlyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyiapkan...';
            
            try {
                const data = await Api.get('/api/reports/cashflow?months=24');
                const cashflow = data.cashflow || [];

                const headers = ['Bulan', 'Pemasukan', 'Pengeluaran', 'Net'];
                const rows = cashflow.map(c => [
                    c.month,
                    c.income || 0,
                    c.expense || 0,
                    (c.income || 0) - (c.expense || 0)
                ]);

                const csvContent = [headers, ...rows].map(r => r.map(v => `"${v}"`).join(',')).join('\n');
                downloadCSV(csvContent, `laporan-bulanan-${new Date().toISOString().slice(0,10)}.csv`);
                
                showToast('CSV laporan bulanan diunduh', 'success');
            } catch (err) {
                console.error('Export failed:', err);
                showToast('Gagal ekspor', 'danger');
            } finally {
                exportMonthlyBtn.disabled = false;
                exportMonthlyBtn.innerHTML = '<i class="bi bi-download me-1"></i>Unduh CSV';
            }
        });
    }

    // Export Net Worth CSV
    const exportNetworthBtn = document.getElementById('exportNetworthCsv');
    if (exportNetworthBtn) {
        exportNetworthBtn.addEventListener('click', async () => {
            exportNetworthBtn.disabled = true;
            exportNetworthBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyiapkan...';
            
            try {
                // We'd need historical net worth data - for now use current
                const data = await Api.get('/api/reports/networth');
                const accounts = data.accounts || [];
                const goals = data.goals || [];

                const headers = ['Jenis', 'Nama', 'Jumlah', 'Mata Uang'];
                const rows = [
                    ...accounts.map(a => ['Aset', a.name, a.balance, a.currency]),
                    ...goals.map(g => ['Target', g.name, g.current_amount, 'IDR'])
                ];

                const csvContent = [headers, ...rows].map(r => r.map(v => `"${v}"`).join(',')).join('\n');
                downloadCSV(csvContent, `networth-${new Date().toISOString().slice(0,10)}.csv`);
                
                showToast('CSV net worth diunduh', 'success');
            } catch (err) {
                console.error('Export failed:', err);
                showToast('Gagal ekspor', 'danger');
            } finally {
                exportNetworthBtn.disabled = false;
                exportNetworthBtn.innerHTML = '<i class="bi bi-download me-1"></i>Unduh CSV';
            }
        });
    }
});

function downloadCSV(content, filename) {
    const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
}

// Handle cashflow period change
document.addEventListener('DOMContentLoaded', () => {
    const periodSelect = document.getElementById('reportCashflowPeriod');
    if (periodSelect) {
        periodSelect.addEventListener('change', async () => {
            try {
                const data = await Api.get(`/api/reports/cashflow?months=${periodSelect.value}`);
                updateCashflowChart(data);
            } catch (err) {
                console.error('Failed to update cashflow:', err);
            }
        });
    }
});