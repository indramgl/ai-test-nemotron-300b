<?php
$title = 'Laporan & Analitik - Personal Finance SaaS';
$content = '
<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold mb-1">Laporan & Analitik</h2>
        <p class="text-muted mb-0">Analisis mendalam performa keuangan Anda</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body">
                <p class="text-muted mb-1 small fw-semibold">Total Aset</p>
                <h3 class="fw-bold mb-0" id="reportTotalAssets">Rp 0</h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body">
                <p class="text-muted mb-1 small fw-semibold">Total Kewajiban</p>
                <h3 class="fw-bold text-danger mb-0" id="reportTotalLiabilities">Rp 0</h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
            <div class="card-body">
                <p class="text-muted mb-1 small fw-semibold">Net Worth</p>
                <h3 class="fw-bold text-success mb-0" id="reportNetWorth">Rp 0</h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
            <div class="card-body">
                <p class="text-muted mb-1 small fw-semibold">Tabungan Target</p>
                <h3 class="fw-bold text-info mb-0" id="reportGoalsProgress">Rp 0</h3>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Cash Flow Bulanan</h5>
                <select class="form-select form-select-sm w-auto" id="reportCashflowPeriod">
                    <option value="12">12 Bulan</option>
                    <option value="24">24 Bulan</option>
                </select>
            </div>
            <div class="card-body">
                <canvas id="cashflowChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0">
                <h5 class="fw-bold mb-0">Komposisi Aset</h5>
            </div>
            <div class="card-body">
                <canvas id="assetsChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Pengeluaran per Kategori (Bulan Ini)</h5>
                <select class="form-select form-select-sm w-auto" id="reportExpenseMonth">
                    <option value="">Bulan Ini</option>
                </select>
            </div>
            <div class="card-body">
                <canvas id="expenseChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0">
                <h5 class="fw-bold mb-0">Progress Target Keuangan</h5>
            </div>
            <div class="card-body" id="goalsProgressContainer">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 mb-0 small">Memuat...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Section -->
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="fw-bold mb-0">Ekspor Data</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="card h-100 border-0 bg-light">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-spreadsheet fs-1 text-success mb-2"></i>
                                <h6 class="fw-bold">Ekspor Transaksi (CSV)</h6>
                                <p class="text-muted small mb-3">Semua transaksi dalam format CSV untuk Excel</p>
                                <button class="btn btn-outline-success" id="exportTransactionsCsv">
                                    <i class="bi bi-download me-1"></i>Unduh CSV
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card h-100 border-0 bg-light">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-text fs-1 text-primary mb-2"></i>
                                <h6 class="fw-bold">Ekspor Laporan Bulanan (CSV)</h6>
                                <p class="text-muted small mb-3">Ringkasan bulanan pemasukan/pengeluaran</p>
                                <button class="btn btn-outline-primary" id="exportMonthlyCsv">
                                    <i class="bi bi-download me-1"></i>Unduh CSV
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card h-100 border-0 bg-light">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-bar-graph fs-1 text-info mb-2"></i>
                                <h6 class="fw-bold">Ekspor Net Worth (CSV)</h6>
                                <p class="text-muted small mb-3">Riwayat net worth per bulan</p>
                                <button class="btn btn-outline-info" id="exportNetworthCsv">
                                    <i class="bi bi-download me-1"></i>Unduh CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';
$extraScripts = '
<script src="/assets/js/reports.js"></script>
';
require __DIR__ . '/../layouts/main.php';