<?php
$title = 'Transaksi - Personal Finance SaaS';
$content = '
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-1">Transaksi</h2>
                <p class="text-muted mb-0">Kelola pemasukan, pengeluaran, dan transfer</p>
            </div>
            <div class="btn-group" role="group">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#transactionModal" data-type="INCOME">
                    <i class="bi bi-plus-circle me-1"></i>Pemasukan
                </button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#transactionModal" data-type="EXPENSE">
                    <i class="bi bi-dash-circle me-1"></i>Pengeluaran
                </button>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#transactionModal" data-type="TRANSFER">
                    <i class="bi bi-arrow-left-right me-1"></i>Transfer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-3 g-2">
    <div class="col-12 col-md-3">
        <select class="form-select form-select-sm" id="filterAccount">
            <option value="">Semua Rekening</option>
        </select>
    </div>
    <div class="col-12 col-md-3">
        <select class="form-select form-select-sm" id="filterCategory">
            <option value="">Semua Kategori</option>
        </select>
    </div>
    <div class="col-12 col-md-3">
        <select class="form-select form-select-sm" id="filterType">
            <option value="">Semua Tipe</option>
            <option value="INCOME">Pemasukan</option>
            <option value="EXPENSE">Pengeluaran</option>
            <option value="TRANSFER">Transfer</option>
        </select>
    </div>
    <div class="col-12 col-md-3">
        <div class="input-group input-group-sm">
            <input type="date" class="form-control" id="filterStartDate">
            <span class="input-group-text">s/d</span>
            <input type="date" class="form-control" id="filterEndDate">
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="transactionsTable">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Kategori</th>
                        <th>Rekening</th>
                        <th>Deskripsi</th>
                        <th class="text-end">Jumlah</th>
                        <th style="width: 80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="transactionsBody">
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 mb-0 small">Memuat...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center" id="paginationContainer" style="display: none;">
            <div class="text-muted small" id="paginationInfo"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- Transaction Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionModalTitle">Tambah Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="transactionForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="id" id="transactionId">
                    <input type="hidden" name="type" id="transactionType">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="txnAccountId" class="form-label fw-semibold">Rekening <span class="text-danger">*</span></label>
                            <select class="form-select" id="txnAccountId" name="account_id" required>
                                <option value="">Pilih rekening</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="txnCategoryId" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="txnCategoryId" name="category_id" required>
                                <option value="">Pilih kategori</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Transfer fields (hidden by default) -->
                    <div class="mb-3 d-none" id="transferFields">
                        <label for="txnToAccountId" class="form-label fw-semibold">Rekening Tujuan <span class="text-danger">*</span></label>
                        <select class="form-select" id="txnToAccountId" name="to_account_id">
                            <option value="">Pilih rekening tujuan</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="txnAmount" class="form-label fw-semibold">Jumlah <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="txnAmount" name="amount" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="txnDate" class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="txnDate" name="transaction_date" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="txnDescription" class="form-label fw-semibold">Deskripsi</label>
                        <textarea class="form-control" id="txnDescription" name="description" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="txnIsRecurring" name="is_recurring">
                        <label class="form-check-label" for="txnIsRecurring">Transaksi berulang (Recurring)</label>
                    </div>
                    
                    <div class="row mb-3 d-none" id="recurringFields">
                        <div class="col-md-6">
                            <label for="txnRecurrencePattern" class="form-label fw-semibold">Pola Ulang</label>
                            <select class="form-select" id="txnRecurrencePattern" name="recurrence_pattern">
                                <option value="daily">Harian</option>
                                <option value="weekly">Mingguan</option>
                                <option value="monthly" selected>Bulanan</option>
                                <option value="yearly">Tahunan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="txnRecurrenceEndDate" class="form-label fw-semibold">Berakhir Pada</label>
                            <input type="date" class="form-control" id="txnRecurrenceEndDate" name="recurrence_end_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="transactionSubmitBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                        <span id="transactionSubmitText">Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
';
$extraScripts = '
<script src="/assets/js/transactions.js"></script>
';
require __DIR__ . '/../layouts/main.php';