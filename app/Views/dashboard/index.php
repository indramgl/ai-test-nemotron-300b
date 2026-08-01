<?php
$title = 'Dashboard - Personal Finance SaaS';
$content = '
<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-1">Dashboard</h2>
        <p class="text-muted">Selamat datang kembali, <span id="userDisplayName">User</span>!</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small fw-semibold">Total Saldo</p>
                        <h3 class="fw-bold mb-0" id="totalBalance">Rp 0</h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small fw-semibold">Pemasukan Bulan Ini</p>
                        <h3 class="fw-bold text-success mb-0" id="monthlyIncome">Rp 0</h3>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                        <i class="bi bi-arrow-down-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small fw-semibold">Pengeluaran Bulan Ini</p>
                        <h3 class="fw-bold text-danger mb-0" id="monthlyExpense">Rp 0</h3>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3">
                        <i class="bi bi-arrow-up-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small fw-semibold">Selisih Bulan Ini</p>
                        <h3 class="fw-bold mb-0" id="monthlyNet">Rp 0</h3>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-3">
                        <i class="bi bi-graph-up fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Cash Flow 6 Bulan Terakhir</h5>
                <select class="form-select form-select-sm w-auto" id="cashflowPeriod">
                    <option value="6">6 Bulan</option>
                    <option value="12">12 Bulan</option>
                </select>
            </div>
            <div class="card-body">
                <canvas id="cashflowChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0">
                <h5 class="fw-bold mb-0">Pengeluaran per Kategori</h5>
            </div>
            <div class="card-body">
                <canvas id="expenseChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Accounts & Recent Transactions -->
<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Rekening Anda</h5>
                <a href="/accounts" class="btn btn-sm btn-outline-primary">Kelola</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="accountsList">
                    <div class="list-group-item text-center py-4 text-muted">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 mb-0 small">Memuat...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Transaksi Terbaru</h5>
                <a href="/transactions" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="recentTransactions">
                    <div class="list-group-item text-center py-4 text-muted">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 mb-0 small">Memuat...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Budget Alerts Modal Trigger -->
<div class="row g-3 mt-3" id="budgetAlertsContainer" style="display: none;">
    <div class="col-12">
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong>Peringatan Anggaran!</strong>
                <span id="budgetAlertText">Anda mendekati batas anggaran untuk beberapa kategori.</span>
            </div>
            <a href="/budgets" class="btn btn-sm btn-outline-warning ms-auto">Lihat Detail</a>
        </div>
    </div>
</div>
';
$extraScripts = '
<script src="/assets/js/dashboard.js"></script>
';
require __DIR__ . '/../layouts/main.php';