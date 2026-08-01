<?php
$title = 'Anggaran - Personal Finance SaaS';
$content = '
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-1">Anggaran Bulanan</h2>
                <p class="text-muted mb-0">Atur batas pengeluaran per kategori dan pantau penggunaan</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#budgetModal">
                <i class="bi bi-plus-circle me-1"></i>Tambah Anggaran
            </button>
        </div>
    </div>
</div>

<!-- Budget Alerts -->
<div class="row mb-3" id="budgetAlertsContainer" style="display: none;">
    <div class="col-12">
        <div id="budgetAlerts"></div>
    </div>
</div>

<!-- Budgets List -->
<div class="row g-3" id="budgetsGrid">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Memuat anggaran...</p>
    </div>
</div>

<!-- Budget Modal -->
<div class="modal fade" id="budgetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="budgetModalTitle">Tambah Anggaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="budgetForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id" id="budgetId">
                    
                    <div class="mb-3">
                        <label for="budgetCategoryId" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                        <select class="form-select" id="budgetCategoryId" name="category_id" required>
                            <option value="">Pilih kategori pengeluaran</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="budgetAmount" class="form-label fw-semibold">Jumlah Anggaran <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="budgetAmount" name="amount" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="budgetPeriod" class="form-label fw-semibold">Periode <span class="text-danger">*</span></label>
                            <select class="form-select" id="budgetPeriod" name="period" required>
                                <option value="monthly">Bulanan</option>
                                <option value="weekly">Mingguan</option>
                                <option value="yearly">Tahunan</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="budgetStartDate" class="form-label fw-semibold">Mulai Tanggal</label>
                            <input type="date" class="form-control" id="budgetStartDate" name="start_date">
                        </div>
                        <div class="col-md-6">
                            <label for="budgetEndDate" class="form-label fw-semibold">Sampai Tanggal (opsional)</label>
                            <input type="date" class="form-control" id="budgetEndDate" name="end_date">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="budgetAlert80" name="alert_threshold_80" checked>
                            <label class="form-check-label" for="budgetAlert80">Notifikasi saat 80%</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="budgetAlert100" name="alert_threshold_100" checked>
                            <label class="form-check-label" for="budgetAlert100">Notifikasi saat 100%</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="budgetSubmitBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        <span id="budgetSubmitText">Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Budget Detail Modal -->
<div class="modal fade" id="budgetDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Anggaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="budgetDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>
';
$extraScripts = '
<script src="/assets/js/budgets.js"></script>
';
require __DIR__ . '/../layouts/main.php';